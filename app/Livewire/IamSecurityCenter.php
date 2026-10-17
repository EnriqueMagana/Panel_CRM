<?php

namespace App\Livewire;

use App\Models\IamAuditLog;
use App\Models\User;
use App\Services\IamAuditLogger;
use App\Services\IamSettings;
use App\Traits\RequiresRecentAuthentication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class IamSecurityCenter extends Component
{
    use RequiresRecentAuthentication;

    #[Url(as: 'tool', history: true)]
    public string $activeTool = 'policies';

    public int $idleTimeoutMinutes = 30;

    public int $absoluteTimeoutMinutes = 480;

    public int $sessionWarningSeconds = 120;

    public int $passwordMinLength = 12;

    public bool $checkCompromisedPasswords = false;

    public bool $saved = false;

    public function mount(IamSettings $settings): void
    {
        if (! in_array($this->activeTool, $this->availableTools(), true)) {
            $this->activeTool = 'policies';
        }

        $this->idleTimeoutMinutes = $settings->idleTimeoutMinutes();
        $this->absoluteTimeoutMinutes = $settings->absoluteTimeoutMinutes();
        $this->sessionWarningSeconds = $settings->warningSeconds();
        $this->passwordMinLength = $settings->passwordMinLength();
        $this->checkCompromisedPasswords = $settings->checkCompromisedPasswords();
    }

    public function selectTool(string $tool): void
    {
        abort_unless(in_array($tool, $this->availableTools(), true), 403);
        $this->activeTool = $tool;
        $this->saved = false;
        $this->resetValidation();
    }

    public function saveIamSettings(IamSettings $settings, IamAuditLogger $audit): void
    {
        $this->authorize('technical_center.manage_iam');
        if (! $this->requireRecentAuthentication()) {
            return;
        }

        $validated = $this->validate([
            'idleTimeoutMinutes' => ['required', 'integer', 'between:5,480'],
            'absoluteTimeoutMinutes' => ['required', 'integer', 'between:60,1440', 'gt:idleTimeoutMinutes'],
            'sessionWarningSeconds' => ['required', 'integer', 'between:30,900'],
            'passwordMinLength' => ['required', 'integer', 'between:12,128'],
            'checkCompromisedPasswords' => ['boolean'],
        ]);

        if ($validated['sessionWarningSeconds'] >= ($validated['idleTimeoutMinutes'] * 60)) {
            $this->addError('sessionWarningSeconds', 'El aviso debe aparecer antes de que expire la sesión.');

            return;
        }

        $settings->update([
            IamSettings::IDLE_TIMEOUT => $validated['idleTimeoutMinutes'],
            IamSettings::ABSOLUTE_TIMEOUT => $validated['absoluteTimeoutMinutes'],
            IamSettings::WARNING_SECONDS => $validated['sessionWarningSeconds'],
            IamSettings::PASSWORD_MIN_LENGTH => $validated['passwordMinLength'],
            IamSettings::CHECK_COMPROMISED => $validated['checkCompromisedPasswords'] ? 'true' : 'false',
        ], Auth::user());

        $audit->record('iam.settings_updated', 'Configuración IAM actualizada.', Auth::user(), Auth::user(), $validated);
        $this->saved = true;
    }

    public function revokeSession(string $sessionId, IamAuditLogger $audit): void
    {
        $this->authorize('technical_center.revoke_sessions');
        if (! $this->requireRecentAuthentication()) {
            return;
        }

        $table = config('session.table', 'sessions');
        abort_if(hash_equals(session()->getId(), $sessionId), 422, 'Usa Cerrar sesión para terminar la sesión actual.');
        $row = DB::table($table)->where('id', $sessionId)->first();
        abort_unless($row, 404);
        $subject = $row->user_id ? User::find($row->user_id) : null;
        $device = $this->deviceName((string) $row->user_agent);
        $browser = $this->browserName((string) $row->user_agent);
        $sessionIds = DB::table($table)
            ->where('user_id', $row->user_id)
            ->where('ip_address', $row->ip_address)
            ->get(['id', 'user_agent'])
            ->filter(fn ($candidate) => $this->deviceName((string) $candidate->user_agent) === $device
                && $this->browserName((string) $candidate->user_agent) === $browser)
            ->pluck('id')
            ->reject(fn (string $id) => hash_equals(session()->getId(), $id));
        $deleted = DB::table($table)->whereIn('id', $sessionIds)->delete();

        $audit->record('session.revoked', 'Sesiones remotas del dispositivo revocadas.', Auth::user(), $subject, [
            'ip_address' => $row->ip_address,
            'sessions_closed' => $deleted,
        ]);
        session()->flash('technical-center-status', $deleted === 1
            ? 'Sesión remota cerrada.'
            : "{$deleted} sesiones del dispositivo fueron cerradas.");
    }

    public function exportAudit(IamAuditLogger $audit): mixed
    {
        $this->authorize('technical_center.export_audit');
        if (! $this->requireRecentAuthentication()) {
            return null;
        }

        $audit->record('iam.audit_exported', 'Bitácora IAM exportada.', Auth::user(), Auth::user());
        $rows = IamAuditLog::query()->with(['actor:id,name', 'subject:id,name'])->latest('id')->limit(5000)->get();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Fecha', 'Evento', 'Descripción', 'Actor', 'Usuario afectado', 'IP']);
            foreach ($rows as $row) {
                fputcsv($output, [$row->created_at?->toDateTimeString(), $row->event, $row->description, $row->actor?->name, $row->subject?->name, $row->ip_address]);
            }
            fclose($output);
        }, 'bitacora-iam-'.now()->format('Y-m-d-His').'.csv');
    }

    public function render(IamSettings $settings)
    {
        $sessions = $this->activeTool === 'sessions' && Auth::user()->can('technical_center.view_sessions')
            ? $this->activeDeviceSessions($settings)
            : collect();
        $auditLogs = $this->activeTool === 'audit' && Auth::user()->can('technical_center.view_audit')
            ? IamAuditLog::query()->with(['actor:id,name', 'subject:id,name'])->latest('id')->limit(100)->get()
            : collect();

        return view('livewire.iam-security-center', compact('sessions', 'auditLogs'));
    }

    private function activeDeviceSessions(IamSettings $settings)
    {
        $table = config('session.table', 'sessions');
        $rows = DB::table($table)
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes($settings->idleTimeoutMinutes())->timestamp)
            ->orderByDesc('last_activity')
            ->get();
        $users = User::query()->whereIn('id', $rows->pluck('user_id')->unique())->get()->keyBy('id');

        return $rows
            ->groupBy(fn ($row) => implode('|', [
                $row->user_id,
                $row->ip_address,
                $this->deviceName((string) $row->user_agent),
                $this->browserName((string) $row->user_agent),
            ]))
            ->map(function ($deviceSessions) use ($users): array {
                $current = $deviceSessions->contains(fn ($row) => hash_equals(session()->getId(), $row->id));
                $row = $current
                    ? $deviceSessions->first(fn ($candidate) => hash_equals(session()->getId(), $candidate->id))
                    : $deviceSessions->first();

                return [
                    'id' => $row->id,
                    'user' => $users->get($row->user_id),
                    'ip' => $row->ip_address ?: 'Sin IP',
                    'device' => $this->deviceName((string) $row->user_agent),
                    'browser' => $this->browserName((string) $row->user_agent),
                    'last_activity' => Carbon::createFromTimestamp($deviceSessions->max('last_activity')),
                    'current' => $current,
                    'instances' => $deviceSessions->count(),
                ];
            })
            ->sortByDesc(fn (array $activeSession) => ($activeSession['current'] ? '1' : '0').$activeSession['last_activity']->timestamp)
            ->values();
    }

    /** @return array<int, string> */
    private function availableTools(): array
    {
        $tools = ['policies'];
        if (Auth::user()->can('technical_center.view_sessions')) {
            $tools[] = 'sessions';
        }
        if (Auth::user()->can('technical_center.view_audit')) {
            $tools[] = 'audit';
        }

        return $tools;
    }

    private function browserName(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Navegador desconocido',
        };
    }

    private function deviceName(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Dispositivo desconocido',
        };
    }
}
