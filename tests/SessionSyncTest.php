<?php

namespace Laraditz\Xendit\Tests;

use Laraditz\Xendit\Enums\SessionStatus;
use Laraditz\Xendit\Models\XenditSession;

class SessionSyncTest extends TestCase
{
    private function sampleResponse(array $overrides = []): array
    {
        return array_merge([
            'payment_session_id' => 'ps-abc123',
            'reference_id' => 'ORDER-SYNC-1',
            'status' => 'COMPLETED',
            'payment_id' => 'py-abc123',
            'payment_token_id' => 'pt-abc123',
            'payment_request_id' => 'pr-abc123',
        ], $overrides);
    }

    public function test_matching_session_gets_fields_synced(): void
    {
        $session = XenditSession::create([
            'reference_id' => 'ORDER-SYNC-1',
            'payment_session_id' => 'ps-abc123',
        ]);

        $synced = XenditSession::syncFromApiResponse($this->sampleResponse());

        $this->assertNotNull($synced);
        $this->assertTrue($synced->is($session));
        $this->assertSame(SessionStatus::Completed, $synced->status);
        $this->assertSame('py-abc123', $synced->payment_id);
        $this->assertSame('pt-abc123', $synced->payment_token_id);
        $this->assertSame('pr-abc123', $synced->payment_request_id);
        $this->assertSame($this->sampleResponse(), $synced->session_details);
    }

    public function test_no_local_match_returns_null_and_makes_no_changes(): void
    {
        $result = XenditSession::syncFromApiResponse($this->sampleResponse(['reference_id' => 'NO-SUCH-ORDER']));

        $this->assertNull($result);
        $this->assertSame(0, XenditSession::count());
    }

    public function test_missing_reference_id_returns_null_without_exception(): void
    {
        $result = XenditSession::syncFromApiResponse($this->sampleResponse(['reference_id' => null]));

        $this->assertNull($result);
    }
}
