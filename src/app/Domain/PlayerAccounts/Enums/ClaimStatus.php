<?php

namespace App\Domain\PlayerAccounts\Enums;

/** Outcome of a single attach/verify attempt in coc_account_claims (specs/13 §3, specs/07). */
enum ClaimStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Rejected = 'rejected';
    case Superseded = 'superseded';
}
