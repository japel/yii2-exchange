<?php namespace Exchange\EWSType; use Exchange\EWSType;
/**
 * Contains EWSType_UpdateItemType.
 */

/**
 * Defines a request to update an item in a mailbox.
 *
 * @package php-ews\Types
 *
 * @todo Extend EWSType_BaseRequestType.
 */
class EWSType_UpdateItemType extends EWSType
{
    /**
     * Identifies the type of conflict resolution to try during an update.
     *
     * The default value is AutoResolve.
     *
     * @since Exchange 2007
     *
     * @var EWSType_ConflictResolutionType
     */
    public $ConflictResolution;

    /**
     * Contains an array of ItemChange elements that identify items and the
     * updates to apply to the items.
     *
     * @since Exchange 2007
     *
     * @var EWSType_NonEmptyArrayOfItemChangesType
     */
    public $ItemChanges;

    /**
     * Describes how the item will be handled after it is updated.
     *
     * he MessageDisposition attribute is required for message items, including
     * meeting messages such as meeting cancellations, meeting requests, and
     * meeting responses.
     *
     * @since Exchange 2007
     *
     * @var EWSType_MessageDispositionType
     */
    public $MessageDisposition;

    /**
     * Identifies the target folder for operations that update, send, and create
     * items in the Exchange store.
     *
     * @since Exchange 2007
     *
     * @var EWSType_TargetFolderIdType
     */
    public $SavedItemFolderId;

    /**
     * Describes how meeting updates are communicated after a calendar item is
     * updated.
     *
     * This attribute is required for calendar items and calendar item
     * occurrences.
     *
     * @since Exchange 2007
     *
     * @var EWSType_CalendarItemUpdateOperationType
     */
    public $SendMeetingInvitationsOrCancellations;

    /**
     * Indicates whether read receipts for the updated item should be
     * suppressed.
     *
     * A value of true indicates that read receipts should be suppressed. A
     * value of false indicates that the read receipts will be sent to the
     * sender.
     *
     * This attribute is optional.
     *
     * @since Exchange 2013 SP1
     *
     * @var boolean
     */
    public $SuppressReadReceipts;
}
