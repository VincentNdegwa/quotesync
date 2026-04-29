<?php

namespace App\Services;

use App\Models\CustomDomain;
use Illuminate\Support\Facades\Http;

class DomainVerificationService
{
    public function initiateVerification(CustomDomain $domain): array
    {
        $token = $domain->generateVerificationToken();

        return [
            'record_name' => $domain->getVerificationRecordName(),
            'record_value' => $domain->getVerificationRecordValue(),
            'record_type' => 'TXT',
            'token' => $token,
        ];
    }

    public function verifyDomain(CustomDomain $domain): bool
    {
        try {
            $recordName = $domain->getVerificationRecordName();
            $expectedValue = $domain->getVerificationRecordValue();

            $dnsRecords = $this->getDnsRecords($domain->domain, 'TXT');

            foreach ($dnsRecords as $record) {
                if (str_contains($record['name'], $recordName) && str_contains($record['value'], $expectedValue)) {
                    $domain->markAsVerified();

                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDnsRecords(string $domain, string $type = 'TXT'): array
    {
        $response = Http::get('https://dns.google/resolve', [
            'name' => $domain,
            'type' => $type,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return $data['Answer'] ?? [];
    }

    public function checkDomainAvailability(string $domain): bool
    {
        try {
            $records = $this->getDnsRecords($domain, 'A');

            return ! empty($records);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function setAsPrimary(CustomDomain $domain): void
    {
        CustomDomain::where('workspace_id', $domain->workspace_id)
            ->where('id', '!=', $domain->id)
            ->update(['is_primary' => false]);

        $domain->update(['is_primary' => true]);
    }
}
