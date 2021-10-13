<?php

namespace Digraph\Modules\ous_event_management\Chunks;

use Digraph\Forms\Fields\FieldValueAutocomplete;

class UNMAffiliation extends AbstractChunk
{
    protected $label = "UNM affiliation";
    const COLLEGES = [
        'Anderson Schools of Management ASM' => 'Anderson Schools of Management',
        'College of Arts & Sciences A&S' => 'College of Arts and Sciences',
        'College of Ed & Human Science COEHS' => 'College of Education and Human Sciences',
        'College of Fine Arts CFA' => 'College of Fine Arts CFA',
        'College of Univ Lbry & Learning Sci' => 'College of University Libraries and Learning Sciences',
        'Continuing Education Cont Ed' => 'Continuing Education',
        'School of Architecture & Planning' => 'School of Architecture and Planning',
        'School of Engineering SOE' => 'School of Engineering',
        'School of Law LAW' => 'School of Law',
        'University College UC' => 'University College',
    ];

    public function userListUser(): ?array
    {
        $user = $this->signup->firstUserListUser();
        if ($user) {
            $user['college'] = @static::COLLEGES[$user['org level 3 desc']] ?? $user['org level 3 desc'];
            $user['department'] = $user['org desc'];
        }
        return $user;
    }

    public function hook_update()
    {
        if (!$this->signup[$this->name]) {
            // try to find previous signups by this user
            $search = $this->signup->cms()->factory()->search();
            $search->where('${dso.type} = :type AND ${signup.for} = :for');
            $search->order('${dso.created.date} desc');
            $search->limit(1);
            if ($result = $search->execute(['type' => $this->signup['dso.type'], 'for' => $this->signup['signup.for']])) {
                $result = array_pop($result);
                $this->signup[$this->name] = $result[$this->name];
                return;
            }
            // try to find user in user lists
            if ($user = $this->userListUser()) {
                $this->signup[$this->name] = [
                    'college' => $user['college'],
                    'department' => $user['department'],
                ];
                return;
            }
        }
    }

    public function body_complete()
    {
        $chunk = $this->signup[$this->name];
        $f = $this->signup->cms()->helper('filters');
        echo "<dl>";
        if (@$chunk['affiliation']) {
            echo "<dt>UNM affiliation</dt><dd>" . $f->sanitize($chunk['affiliation']) . "</dd>";
        }
        if (@$chunk['college']) {
            echo "<dt>School/College</dt><dd>" . $f->sanitize($chunk['college']) . "</dd>";
        }
        if (@$chunk['department']) {
            echo "<dt>Department</dt><dd>" . $f->sanitize($chunk['department']) . "</dd>";
        }
        if (@$chunk['msc']) {
            echo "<dt>Mail stop code</dt><dd>" . $f->sanitize($chunk['msc']) . "</dd>";
        }
        echo "</dl>";
    }

    protected function form_map(): array
    {
        $name = $this->name;
        $types = ['event-signup'];
        $map = [
            'affiliation' => [
                'label' => 'UNM affiliation',
                'class' => 'select',
                'field' => "$name.affiliation",
                'options' => [
                    'Faculty' => 'Faculty',
                    'Staff' => 'Staff',
                    'Administration' => 'Administration',
                    'Student' => 'Student',
                    'Alumni' => 'Alumni',
                    'Other' => 'Other',
                    'None' => 'None',
                ],
                'required' => true,
                'weight' => 100,
            ],
            'college' => [
                'label' => 'School/College (if applicable)',
                'class' => FieldValueAutocomplete::class,
                'field' => "$name.college",
                'extraConstructArgs' => [
                    $types, //types
                    ["$name.college"], //fields
                    true, //allow adding
                ],
                'required' => false,
                'weight' => 100,
            ],
            'department' => [
                'label' => 'Department (if applicable)',
                'class' => FieldValueAutocomplete::class,
                'field' => "$name.department",
                'extraConstructArgs' => [
                    $types, //types
                    ["$name.department"], //fields
                    true, //allow adding
                ],
                'required' => false,
                'weight' => 100,
            ]
        ];
        // insert existing value into dropdown, so users can edit other fields
        if ($this->signup["$name.affiliation"]) {
            $map['affiliation']['options'][$this->signup["$name.affiliation"]] = $this->signup["$name.affiliation"];
        }
        // allow editors to override and enter in any affiliation value
        if (in_array('editor', $this->signup->cms()->helper('users')->groups())) {
            $map['affiliation'] = [
                'label' => 'UNM position/affiliation',
                'class' => FieldValueAutocomplete::class,
                'field' => "$name.affiliation",
                'extraConstructArgs' => [
                    $types, //types
                    ["$name.affiliation"], //fields
                    true, //allow adding
                ],
                'required' => false,
                'weight' => 100,
            ];
        }
        return $map;
    }
}
