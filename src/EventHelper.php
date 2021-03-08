<?php
namespace Digraph\Modules\ous_event_management;

use Digraph\Data\DatastoreNamespace;
use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;

class EventHelper extends AbstractHelper
{
    protected $mailTemplates;

    public function reportDatastore(): DatastoreNamespace
    {
        return $this->cms->helper('datastore')->namespace('event_management_reports');
    }

    public function datastore(): DatastoreNamespace
    {
        return $this->cms->helper('datastore')->namespace('event_management');
    }

    public function saveReportPreset(string $title, string $filter, string $sort, string $columns): string
    {
        $r = [
            'f' => $filter,
            't' => $title,
            's' => $sort,
            'c' => $columns,
        ];
        $id = md5(strtolower($title));
        $this->reportDatastore()->set($id, $r);
        return $id;
    }

    public function mailTemplates(array $set = null)
    {
        if ($set) {
            $this->datastore()->set('mailTemplates', $set);
            $this->mailTemplates = null;
        }
        if ($this->mailTemplates === null) {
            $this->mailTemplates = $this->datastore()->get('mailTemplates');
        }
        return $this->mailTemplates;
    }

    public function addMailTemplate($name, $subject, $body)
    {
        $templates = $this->mailTemplates();
        $templates[$name] = [
            'subject' => $subject,
            'body' => $body,
        ];
        $this->mailTemplates($templates);
    }

    public function mailTemplate($name)
    {
        return @$this->mailTemplates()[$name];
    }

    public function sendMailTemplate(Signup $signup, $template)
    {
        if (!($template = $this->mailTemplate($template))) {
            return false;
        }
        $this->sendMail(
            $signup,
            $template['subject'],
            $this->cms->helper('filters')->filterContentField($template['body'], $signup['dso.id'])
        );
        return true;
    }

    public function sendMail($to_or_signup, $subject, $body_html, $fromChair = false)
    {
        if ($to_or_signup instanceof Signup) {
            if (!$to_or_signup->signupWindow()['signupwindow.email']) {
                // signup window has notification emails disabled
                return;
            }
            $subject .= ' [Signup #' . $to_or_signup['dso.id'] . ']';
        }
        $subject = $this->emailCodes($subject, $to_or_signup);
        $body_html = $this->emailCodes($body_html, $to_or_signup);
        //prepare message for queuing
        $message = $this->cms->helper('mail')->message();
        $message->addTag('events');
        //set subject
        $message->setSubject($subject);
        //set to address (either address directly, or submitter from Proposal)
        if ($to_or_signup instanceof Signup) {
            foreach ($to_or_signup->notificationEmails() as $to) {
                $message->addTo($to);
            }
            $message->addTag($to_or_signup['dso.id']);
            if ($window = $to_or_signup->signupWindow()) {
                $message->addTag($window['dso.id']);
            }
            if ($group = $to_or_signup->eventGroup()) {
                $message->addTag($group['dso.id']);
            }
            foreach ($to_or_signup->allEvents() as $event) {
                $message->addTag($event['dso.id']);
            }
        } else {
            $message->addTo($to_or_signup);
        }
        //set body
        $message->setBody($body_html);
        //set from/ccs/bccs
        $message->setFrom($this->cms->config['events.email.from']);
        //set debug bcc
        foreach ($this->cms->config['events.email.debug_bcc'] as $bcc) {
            $message->addBCC($bcc);
        }
        //attempt to send and return result
        $this->cms->helper('mail')->send($message);
        return true;
    }

    protected function emailCodes($text, $signup)
    {
        $s = $this->cms->helper('strings');
        if ($signup instanceof Signup) {
            $codes = [
                "contact_fname" => $signup['contact.firstname'],
                "contact_lname" => $signup['contact.lastname'],
                "signup_id" => $signup['dso.id'],
                "signup_url" => $signup->url()->string(),
                "signup_link" => $signup->link(),
            ];
        } else {
            $codes = [];
        }
        $codes['events_list'] = '';
        if ($signup) {
            if ($group = $signup->eventGroup()) {
                $codes['group_name'] = $group->title();
                $codes['group_url'] = $group->url()->string();
                $codes['group_link'] = $group->link();
            }
            if ($window = $signup->signupWindow()) {
                $codes['window_name'] = $window->title();
                $codes['window_url'] = $window->url()->string();
                $codes['window_link'] = $window->link();
                $codes['window_time_start'] = $s->datetime($window['signupwindow.time.start']);
                $codes['window_time_end'] = $s->datetime($window['signupwindow.time.end']);
            }
            $events = $signup->allEvents();
            if (count($events) > 1) {
                $list = '<p>Signed up for event' . (count($events) == 1 ? '' : 's') . ':</p>';
                $list .= '<ul>';
                $list .= implode(PHP_EOL, array_map(function ($e) {
                    return '<li>' . $e->link() . '</li>';
                }, $events));
                $list .= '</ul>';
                $codes['events_list'] = $list;
            }
            if ($page = $signup->personalizedPage()) {
                $codes['page_url'] = $page->url();
            }
        }
        foreach ($codes as $code => $rep) {
            $text = str_replace('[' . $code . ']', $rep, $text);
        }
        return $text;
    }
}
