<?php

namespace App\Domain;

use App\Events\CrmEvent;
use App\Models\FileNote;
use App\Models\ShipmentFile;
use App\Models\User;

class NoteService
{
    /** Saves a note and notifies everyone @mentioned by name or initials. */
    public function add(ShipmentFile $f, User $user, string $body): FileNote
    {
        preg_match_all('/@([\p{L}\p{N}_.-]+)/u', $body, $m);
        $tokens = collect($m[1])->map(fn ($t) => mb_strtolower($t))->unique();
        $mentioned = $tokens->isEmpty() ? collect() : User::where('active', true)->get()->filter(function (User $u) use ($tokens) {
            $first = mb_strtolower(explode(' ', $u->name)[0]);
            return $tokens->contains($first) || $tokens->contains(mb_strtolower((string) $u->initials));
        })->reject(fn ($u) => $u->id === $user->id)->values();

        $note = FileNote::create(['file_id' => $f->id, 'user_id' => $user->id, 'body' => $body, 'mentions' => $mentioned->pluck('id')->all()]);
        if ($mentioned->isNotEmpty()) {
            event(new CrmEvent('mentioned', ['name' => $user->name, 'ref' => $f->number], route('files.show', $f).'#notes', [], $mentioned->pluck('id')->all(), $user, false));
        }
        return $note;
    }
}
