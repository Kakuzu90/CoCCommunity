<?php

namespace App\Domain\Media\Enums;

// Lifecycle from specs/10 §3 / FR-MEDIA-7.
enum MediaStatus: string
{
    case Pending = 'pending';       // intent created, awaiting the client PUT
    case Uploaded = 'uploaded';     // client called complete; queued for processing
    case Processing = 'processing'; // ProcessMediaJob is working
    case Ready = 'ready';           // validated, re-encoded, variants written
    case Failed = 'failed';         // processing failed; owner can re-upload
    case Quarantined = 'quarantined'; // suspicious; retained 30 days for review
    case Deleting = 'deleting';     // scheduled for storage removal
}
