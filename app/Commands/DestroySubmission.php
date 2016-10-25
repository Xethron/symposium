<?php namespace App\Commands;

use App\Models\Conference;
use App\Commands\Command;
use Submission;
use Talk;
use TalkRevision;

class DestroySubmission extends Command
{
    private $conferenceId;
    private $talkId;

    public function __construct($conferenceId, $talkId)
    {
        $this->conferenceId = $conferenceId;
        $this->talkId = $talkId;
    }

    public function handle()
    {
        $conference = Conference::findOrFail($this->conferenceId);
        $revisionIds = Talk::findOrFail($this->talkId)->revisions->pluck('id');

        $talkRevision = $conference->submissions()
            ->whereIn('talk_revision_id', $revisionIds)
            ->firstOrFail();

        $conference->submissions()->detach($talkRevision);
    }
}
