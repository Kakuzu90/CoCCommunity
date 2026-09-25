<?php

namespace App\Domain\PlayerAccounts\Enums;

/** Why a dispute action could not proceed (specs/13 §5). Each maps to a precise user-facing message. */
enum DisputeError: string
{
    case NoConflict = 'no_conflict';
    case SelfDispute = 'self_dispute';
    case AlreadyDisputing = 'already_disputing';
    case UnderReview = 'under_review';
    case TooManyOpen = 'too_many_open';
    case Barred = 'barred';
    case NotResolvable = 'not_resolvable';
    case AdminIsParty = 'admin_is_party';
    case NotYours = 'not_yours';

    public function message(): string
    {
        return match ($this) {
            self::NoConflict => 'That tag is not verified by anyone, so there is nothing to dispute. Add it with your in-game token instead.',
            self::SelfDispute => 'You already hold this tag. You cannot open a dispute against yourself.',
            self::AlreadyDisputing => 'You already have an open dispute for this tag.',
            self::UnderReview => 'This tag is already under review in another dispute. Only one dispute runs at a time.',
            self::TooManyOpen => 'You can have at most two open disputes at a time. Wait for one to be resolved.',
            self::Barred => 'You have had two disputes denied recently and cannot file another for 90 days.',
            self::NotResolvable => 'This dispute has already been resolved.',
            self::AdminIsParty => 'You cannot resolve a dispute you are a party to.',
            self::NotYours => 'This dispute is not yours to respond to.',
        };
    }
}
