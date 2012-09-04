<?php

namespace Sabre\VObject\Splitter;

use Sabre\VObject;

/**
 * Splitter
 *
 * This class is responsible for splitting up iCalendar objects.
 *
 * This class expects a single VCALENDAR object with one or more
 * calendar-objects inside. Objects with identical UID's will be combined into
 * a single object.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Dominik Tobschall
 * @author Armin Hackmann
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class ICalendar implements SplitterInterface {

    /**
     * Timezones
     *
     * @var array
     */
    public $vtimezones = array();

    /**
     * File handle
     *
     * @var resource
     */
    protected $objects = array();

    /**
     * Constructor
     *
     * The splitter should receive an readable file stream as it's input.
     *
     * @param resource $input
     */
    public function __construct($input) {

        $data = VObject\Reader::read(stream_get_contents($input));
        $vtimezones = array();
        $components = array();

        foreach($data->children as $component) {
            if (!$component instanceof VObject\Component) {
                continue;
            }

            // Get all timezones
            if ($component->name === 'VTIMEZONE') {
                $this->vtimezones[(string)$component->TZID] = $component;
                continue;
            }

            // Get component UID for recurring Events search
            if($component->uid) {
                $uid = (string)$component->UID;
            } else {
                $uid = '';
            }

            // Take care of recurring events
            if (!array_key_exists($uid, $this->objects)) {
                $this->objects[$uid] = VObject\Component::create('VCALENDAR');
            }

            $this->objects[$uid]->add($component);
        }

    }

    /**
     * Every time getNext() is called, a new object will be parsed, until we
     * hit the end of the stream.
     *
     * When the end is reached, null will be returned.
     *
     * @return Sabre\VObject\Component|null
     */
    public function getNext() {

        if($object=current($this->objects)) {
            next($this->objects);
            return $object;
        } else {
            return null;
        }

   }

}
