<?php

declare(strict_types=1);

namespace App\Service;

final readonly class OrderAdminActions
{
    public function __construct(
        public bool $canAccept,
        public bool $canSendQuote,
        public bool $canReject,
        public bool $canCancel,
        public bool $canComplete,
        public bool $canMarkPaid,
        public bool $canRemindPayment,
        public bool $canRequestInfo,
    ) {
    }
}
