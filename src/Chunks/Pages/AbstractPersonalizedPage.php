<?php
namespace Digraph\Modules\ous_event_management\Chunks\Pages;

use Digraph\FileStore\FileStoreFile;
use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;
use Digraph\Modules\ous_event_management\Chunks\GradPhotoField;
use Digraph\Urls\Url;

abstract class AbstractPersonalizedPage extends AbstractChunk
{
    protected $label = 'Personalized page';
    const WEIGHT = 200;

    protected function buttonText_cancelEdit()
    {
        return "cancel editing";
    }

    protected function buttonText_edit()
    {
        return "edit personalized page";
    }

    protected function buttonText_editIncomplete()
    {
        return "edit personalized page";
    }

    public function pagePhoto(): ?FileStoreFile
    {
        $fs = $this->signup->cms()->helper('filestore');
        $list = $fs->list($this->signup, 'personalizedpagephoto');
        return array_pop($list);
    }

    public function url(): Url
    {
        return $this->signup->url('personalpage');
    }

    public function pageBody(): string
    {
        $out = '<div class="digraph-block personalized-page-body">';
        if ($file = $this->pagePhoto()) {
            $out .= '<img src="' . $file->imageUrl('signup-portrait') . '" class="personalized-photo" />';
        }
        if ($message = $this->signup['personalpage.message']) {
            $message = $this->signup->cms()->helper('filters')->filterPreset($message, 'text-safe');
            $out .= '<blockquote>' . $message . '</blockquote>';
        }
        $out .= $this->degreesHTML($this->signup->degrees()->degrees());
        $out .= '</div>';
        return $out;
    }

    protected function degreesHTML(array $degrees)
    {
        $out = '';
        if (!$degrees) {
            $out .= '<div class="notification notification-error">No degrees found.</div>';
        }
        foreach ($degrees as $d) {
            $out .= '<div class="digraph-card">';
            $out .= '<div><strong>' . @$d['degree'] . '</strong></div>';
            if (@$d['major']) {
                $out .= '<div><em>Major:</em> ' . $d['major'];
                if (@$d['second major']) {
                    $out .= ', ' . $d['second major'];
                }
                $out .= "</div>";
            }
            if (@$d['minor']) {
                $out .= '<div><em>Minor:</em> ' . $d['minor'];
                if (@$d['second minor']) {
                    $out .= ', ' . $d['second minor'];
                }
                $out .= "</div>";
            }
            if (@$d['honors']) {
                $out .= '<div><em>University Honors:</em> ' . $d['honors'] . '</div>';
            }
            if (@$d['academic period']) {
                $out .= '<div><em>' . $d['graduation status'] . ' ' . $d['academic period'] . '</em></div>';
            }
            $out .= '</div>';
        }
        return $out;
    }

    public function pageActive(): bool
    {
        return !!$this->signup['personalpage.activate'];
    }

    public function pageModeration(): ?bool
    {
        if ($this->signup['moderation.hash'] == $this->pageContentHash()) {
            if ($this->signup['moderation.state'] == 'approved') {
                return true;
            } elseif ($this->signup['moderation.state'] == 'denied') {
                return false;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function pageModerate(bool $state)
    {
        $this->signup['moderation.state'] = $state ? 'approved' : 'denied';
        $this->signup['moderation.hash'] = $this->pageContentHash();
        $this->signup->update();
    }

    public function pageModerationEscalate()
    {
        $this->signup['moderation.state'] = 'escalated';
        $this->signup['moderation.hash'] = $this->pageContentHash();
        $this->signup->update();
    }

    public function pageModerationIsEscalated()
    {
        return ($this->signup['moderation.escalate'] === true) && ($this->signup['moderation.hash'] == $this->pageContentHash());
    }

    public function hook_update()
    {
        // clear moderation state if hash no longer matches
        if ($this->signup['moderation.hash'] != $this->pageContentHash()) {
            $this->signup['moderation'] = [
                'state' => 'pending',
                'hash' => $this->pageContentHash(),
            ];
        }
        // check for changes to moderation state
        switch ($this->signup->changes()['moderation']['state']) {
            case 'approved':
                // send email on moderation approval
                $this->signup->helper()->sendMailTemplate($this->signup, 'signup_page_approved');
                break;
            case 'denied':
                // send email on moderation denied
                $this->signup->helper()->sendMailTemplate($this->signup, 'signup_page_denied');
                break;
        }
    }

    public function pageContentHash(): string
    {
        return md5(serialize([
            $this->signup['contact.firstname'],
            $this->signup['contact.lastname'],
            $this->signup['personalpage'],
            $this->signup['filestore.personalizedpagephoto'],
        ]));
    }

    public function body_complete()
    {
        $n = $this->signup->cms()->helper('notifications');
        // self-activation status
        if (!$this->pageActive()) {
            $n->printWarning('Personalized page is not currently activated, click "edit personalized page" to edit your page content');
            return;
        } else {
            // moderation status
            if ($this->pageModeration() === null) {
                $n->printNotice('Personalized page content is awaiting moderation');
            } elseif ($this->pageModeration() === false) {
                $n->printError('Personalized page content has been blocked by a moderator, please remove anything offensive from your signup. After making an edit you will be automatically placed back in the moderation queue.');
            } else {
                $n->printConfirmation('<a target="_blank" href="' . $this->url() . '">Personalized page is online here</a><br>Note that editing this section or your name will cause your page to be taken offline so it can be re-moderated.');
            }
            // display preview
            echo $this->pageBody();
        }
    }

    public function body_incomplete()
    {
        $this->body_complete();
    }

    protected function form_map(): array
    {
        return [
            'photo' => [
                'label' => 'My photo',
                'class' => GradPhotoField::class,
                'weight' => 100,
                'required' => false,
                'extraConstructArgs' => [
                    'personalizedpagephoto', // filestore path
                    null, // extensions (there are sane defaults)
                    5 * 1024 * 1024, // max file size
                ],
            ],
            'message' => [
                'label' => 'Personalized message',
                'field' => 'personalpage.message',
                'class' => 'textarea',
                'weight' => 200,
                'required' => false,
                'tips' => [
                    'Use this area to share thanks, words of wisdom, or anything you\'d like to tell the world about this achievement',
                ],
            ],
            'activate' => [
                'label' => 'Activate my personalized graduate page. Content will be made available online after moderation.',
                'field' => 'personalpage.activate',
                'class' => 'checkbox',
                'weight' => 500,
                'default' => true,
                'required' => false,
            ],
        ];
    }
}
