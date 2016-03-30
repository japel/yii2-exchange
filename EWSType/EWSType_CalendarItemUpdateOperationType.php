<?php namespace Exchange\EWSType; use Exchange\EWSType;
/**
 * Contains EWSType_CalendarItemUpdateOperationType.
 */

/**
 * Defines how meeting updates are communicated after a calendar item is
 * updated.
 *
 * @package php-ews\Enumerations
 */
class EWSType_CalendarItemUpdateOperationType extends EWSType
{
    /**
     * The calendar item is updated and the meeting update is sent to all
     * attendees but is not saved in the Sent Items folder.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    const SEND_ONLY_TO_ALL = 'SendOnlyToAll';

    /**
     * The calendar item is updated and the meeting update is sent only to
     * attendees that are affected by the change in the meeting.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    const SEND_ONLY_TO_CHANGED = 'SendOnlyToChanged';

    /**
     * The calendar item is updated, the meeting update is sent to all
     * attendees, and a copy is saved in the Sent Items folder.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    const SEND_TO_ALL_AND_SAVE_COPY = 'SendToAllAndSaveCopy';

    /**
     * The calendar item is updated, the meeting update is sent to all attendees
     * that are affected by the change in the meeting, and a copy is saved in
     * the Sent Items folder.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    const SEND_TO_CHANGED_AND_SAVE_COPY = 'SendToChangedAndSaveCopy';

    /**
     * The calendar item is updated but updates are not sent to attendees.
     *
     * @since Exchange 2007
     *
     * @var string
     */
    const SEND_TO_NONE = 'SendToNone';

    /**
     * Element value.
     *
     * @var string
     */
    public $_ ;

    /**
     * Returns the value of this object as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this-> _ ;
    }
}
