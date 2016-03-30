<?php

namespace Exchange;

use Exchange\EWSType\EWSType_ArrayOfStringsType;
use Exchange\EWSType\EWSType_BodyType;
use Exchange\EWSType\EWSType_BodyTypeType;
use Exchange\EWSType\EWSType_CalendarItemCreateOrDeleteOperationType;
use Exchange\EWSType\EWSType_CalendarItemType;
use Exchange\EWSType\EWSType_CreateItemType;
use Exchange\EWSType\EWSType_DistinguishedPropertySetIdType;
use Exchange\EWSType\EWSType_FieldOrderType;
use Exchange\EWSType\EWSType_FindItemType;
use Exchange\EWSType\EWSType_GetItemType;
use Exchange\EWSType\EWSType_ItemChangeType;
use Exchange\EWSType\EWSType_ItemClassType;
use Exchange\EWSType\EWSType_ItemIdType;
use Exchange\EWSType\EWSType_ItemQueryTraversalType;
use Exchange\EWSType\EWSType_ItemResponseShapeType;
use Exchange\EWSType\EWSType_DefaultShapeNamesType;
use Exchange\EWSType\EWSType_CalendarViewType;
use Exchange\EWSType\EWSType_LegacyFreeBusyType;
use Exchange\EWSType\EWSType_MapiPropertyTypeType;
use Exchange\EWSType\EWSType_NonEmptyArrayOfAllItemsType;
use Exchange\EWSType\EWSType_NonEmptyArrayOfBaseFolderIdsType;
use Exchange\EWSType\EWSType_DistinguishedFolderIdType;
use Exchange\EWSType\EWSType_DistinguishedFolderIdNameType;
use Exchange\EWSType\EWSType_ContactsViewType;
use Exchange\EWSType\EWSType_NonEmptyArrayOfBaseItemIdsType;
use Exchange\EWSType\EWSType_NonEmptyArrayOfFieldOrdersType;
use Exchange\EWSType\EWSType_NonEmptyArrayOfPathsToElementType;
use Exchange\EWSType\EWSType_PathToExtendedFieldType;
use Exchange\EWSType\EWSType_PathToUnindexedFieldType;
use Exchange\EWSType\EWSType_SensitivityChoicesType;
use Exchange\EWSType\EWSType_SetItemFieldType;
use Exchange\EWSType\EWSType_SyncFolderItemsType;
use Exchange\EWSType\EWSType_UpdateItemType;
use yii\helpers\ArrayHelper;

class Ews
{
    public $ews;

    public function __construct(
        $server = null,
        $username = null,
        $password = null,
        $version = ExchangeWebServices::VERSION_2007
    ) {
        @set_exception_handler(array($this, 'exceptionHandler'));
        $this->initialize();
        $this->ews = new ExchangeWebServices($server, $username, $password, $version);
    }

    public static function getServerVersions()
    {
        return [
            ExchangeWebServices::VERSION_2007 => 'Exchange2007',
            ExchangeWebServices::VERSION_2007_SP1 => 'Exchange2007_SP1',
            ExchangeWebServices::VERSION_2007_SP2 => 'Exchange2007_SP2',
            ExchangeWebServices::VERSION_2007_SP3 => 'Exchange2007_SP3',
            ExchangeWebServices::VERSION_2010 => 'Exchange2010',
            ExchangeWebServices::VERSION_2010_SP1 =>'Exchange2010_SP1',
            ExchangeWebServices::VERSION_2010_SP2 => 'Exchange2010_SP2',
        ];
    }

    private function initialize()
    {
        $directories = ['EWSType', 'NTLMSoapClient'];
        foreach ($directories as $directory) {
            $dir = dirname(__DIR__).'/yii2-ews/'.$directory;
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while ((($file = readdir($dh)) !== false)) {
                        if (pathinfo($file)['extension'] == 'php') {
                            $name = strstr($file, '.php', true);
                            $class = 'Exchange\\'.$directory.'\\'.$name;
                            $classPath = __DIR__.'/'.$directory.'/'.$name.'.php';
                            include_once($classPath);
                        }
                    }
                    closedir($dh);
                }
            }
        }
    }

    public function getCalendarEvents($start_date = null, $end_date = null)
    {
        $currentDate = strtotime('now');
        $startDate = (!$start_date) ? date('c', strtotime('-15 days', $currentDate))
            : date('c', strtotime($start_date));
        $endDate = (!$end_date) ? date('c', strtotime('+15 days', $currentDate))
            : date('c', strtotime($end_date));
        $myEvents = [];
        $request = new EWSType_FindItemType();
        // Use this to search only the items in the parent directory in question or use ::SOFT_DELETED
        // to identify "soft deleted" items, i.e. not visible and not in the trash can.
        $request->Traversal = EWSType_ItemQueryTraversalType::SHALLOW;
        // This identifies the set of properties to return in an item or folder response
        $request->ItemShape = new EWSType_ItemResponseShapeType();
        $request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::DEFAULT_PROPERTIES;

        // Define the timeframe to load calendar items
        $request->CalendarView = new EWSType_CalendarViewType();
        $request->CalendarView->StartDate = $startDate;
        $request->CalendarView->EndDate = $endDate;

        // Only look in the "calendars folder"
        $request->ParentFolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
        $request->ParentFolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
        $request->ParentFolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::CALENDAR;

        // Send request
        $response = $this->ews->FindItem($request);
        //var_dump($response, $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->CalendarItem);
        // Loop through each item if event(s) were found in the timeframe specified
        if ($response->ResponseMessages->FindItemResponseMessage->RootFolder->TotalItemsInView > 0) {
            $events = $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->CalendarItem;
            foreach ($events as $event) {
                $myEvents[] = $this->createEventArrayFromResponse($event);
            }
        } else {
            // No items returned
        }
        return $myEvents;
    }


    private function getContactList()
    {
        $request = new EWSType_FindItemType();

        $request->ItemShape = new EWSType_ItemResponseShapeType();
        $request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;

        $request->ContactsView = new EWSType_ContactsViewType();
        $request->ContactsView->InitialName = 'a';
        $request->ContactsView->FinalName = 'z';

        $request->ParentFolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
        $request->ParentFolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
        $request->ParentFolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::CONTACTS;

        $request->Traversal = EWSType_ItemQueryTraversalType::SHALLOW;

        $response = $this->ews->FindItem($request);
        return $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->Contact;
    }

    public function getContactListEmails()
    {
        $emails = [];

        foreach ($this->getContactList() as $contact) {
            if (is_array($contact->EmailAddresses->Entry)) {
                $emails[] = [
                    'id' => $contact->Subject,
                    'email' => ArrayHelper::getColumn($contact->EmailAddresses->Entry, '_')
                ];
            } else {
                $emails[] = [
                    'id' => $contact->Subject,
                    'email' => $contact->EmailAddresses->Entry->_
                ];
            }
        }
        return $emails;
    }

    public function getEmails()
    {
        $request = new EWSType_FindItemType();

        $request->ItemShape = new EWSType_ItemResponseShapeType();
        $request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::DEFAULT_PROPERTIES;

        $request->Traversal = EWSType_ItemQueryTraversalType::SHALLOW;

        $request->ParentFolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
        $request->ParentFolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
        $request->ParentFolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::INBOX;

// sort order
        $request->SortOrder = new EWSType_NonEmptyArrayOfFieldOrdersType();
        $request->SortOrder->FieldOrder = array();
        $order = new EWSType_FieldOrderType();
// sorts mails so that oldest appear first
// more field uri definitions can be found from types.xsd (look for UnindexedFieldURIType)
        $order->FieldURI->FieldURI = 'item:DateTimeReceived';
        $order->Order = 'Ascending';
        $request->SortOrder->FieldOrder[] = $order;

        $response = $this->ews->FindItem($request);
        return $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items;
    }

    public function getEvent($itemId)
    {
        $request = new EWSType_GetItemType();

        $request->ItemShape = new EWSType_ItemResponseShapeType();
        $request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;
//        $request->ItemShape->AdditionalProperties = new EWSType_NonEmptyArrayOfPathsToElementType();
/*
        $extendedProperty = new EWSType_PathToExtendedFieldType();
        $extendedProperty->PropertyName = 'OnlineMeetingExternalLink';
        $extendedProperty->PropertyType = EWSType_MapiPropertyTypeType::STRING;
        $extendedProperty->DistinguishedPropertySetId = EWSType_DistinguishedPropertySetIdType::PUBLIC_STRINGS;
        $request->ItemShape->AdditionalProperties->ExtendedFieldURI = array($extendedProperty);
*/
        $request->ItemIds = new EWSType_NonEmptyArrayOfBaseItemIdsType();
        $request->ItemIds->ItemId = new EWSType_ItemIdType();
        $request->ItemIds->ItemId->Id = $itemId;

        $response = $this->ews->GetItem($request);
        /**
         * Not returning yet the Category
         */
        return $response->ResponseMessages->GetItemResponseMessage->Items->CalendarItem;
    }

    public function synchronize($sync_state = null)
    {
        $sync_state = (!is_null($sync_state)) ? $sync_state : null;

        $currentDate = strtotime('now');
        $startDate = date('c', strtotime('-15 days', $currentDate));
        $endDate = date('c', strtotime('+15 days', $currentDate));

        $syncArray = [];
        $request = new EWSType_SyncFolderItemsType();
        $request->SyncState = $sync_state;
        $request->MaxChangesReturned = 512;
        $request->ItemShape = new EWSType_ItemResponseShapeType;
        $request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;

        $request->CalendarView = new EWSType_CalendarViewType();
        $request->CalendarView->StartDate = $startDate;
        $request->CalendarView->EndDate = $endDate;

        $request->SyncFolderId = new EWSType_NonEmptyArrayOfBaseFolderIdsType;
        $request->SyncFolderId->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType;
        $request->SyncFolderId->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::CALENDAR;

        $response = $this->ews->SyncFolderItems($request);
        $syncArray['status_id'] = ($response->ResponseMessages->SyncFolderItemsResponseMessage->ResponseClass == 'Success')
            ? $response->ResponseMessages->SyncFolderItemsResponseMessage->SyncState
            : null;

        $changes = $response->ResponseMessages->SyncFolderItemsResponseMessage->Changes;

        //created events
        if (property_exists($changes, 'Create')) {
            if (is_object($changes->Create)) {
                $syncArray['events']['created'][] = $this->createEventArrayFromResponse($changes->Create->CalendarItem);
            } else {
                foreach ($changes->Create as $event) {
                    $syncArray['events']['created'][] = $this->createEventArrayFromResponse($event->CalendarItem);
                    //$this->getEvent($event->CalendarItem->ItemId->Id);
                }
            }
        }
        //updated events
        if (property_exists($changes, 'Update')) {
            if (is_object($changes->Update)) {
                $syncArray['events']['updated'][] = $this->createEventArrayFromResponse($changes->Update->CalendarItem);
            } else {
                foreach ($changes->Update as $event) {
                    $syncArray['events']['updated'][] = $this->createEventArrayFromResponse($event->CalendarItem);
                }
            }
        }
        //deleted events
        if (property_exists($changes, 'Delete')) {
            if (is_object($changes->Delete)) {
                $syncArray['events']['deleted'][] = $this->createEventArrayFromResponse($changes->Delete);
            } else {
                foreach ($changes->Delete as $event) {
                    $syncArray['events']['deleted'][] = $this->createEventArrayFromResponse($event);
                }
            }
        }
        return $syncArray;
    }

    public function createCalendarEvent($myEvent)
    {
        $replyArray = null;
        $subject = $myEvent['subject'];

        $request = new EWSType_CreateItemType();
        $request->Items = new EWSType_NonEmptyArrayOfAllItemsType();
        $request->Items->CalendarItem = new EWSType_CalendarItemType();

        $request->Items->CalendarItem->Subject = $subject;

        $date1 = new \DateTime($myEvent['start']);
        $DateStart = $date1->format('Y-m-d H:i:00');
        $date = new \DateTime($DateStart);
        $request->Items->CalendarItem->Start = $date->format('c');

        $date1 = new \DateTime($myEvent['end']);
        $DateEnd = $date1->format('Y-m-d H:i:00');
        $date = new \DateTime($DateEnd);
        $request->Items->CalendarItem->End = $date->format('c');

        $request->Items->CalendarItem->ReminderIsSet = false;

        $request->Items->CalendarItem->ReminderMinutesBeforeStart = 15;

        /**
         * No Body for Now
         */
        //$request->Items->CalendarItem->Body = new EWSType_BodyType();
        //$request->Items->CalendarItem->Body->BodyType = EWSType_BodyTypeType::TEXT;

        //$request->Items->CalendarItem->Body->_ = '';

        $request->Items->CalendarItem->ItemClass = new EWSType_ItemClassType();
        $request->Items->CalendarItem->ItemClass->_ = EWSType_ItemClassType::APPOINTMENT;

        $request->Items->CalendarItem->Sensitivity = new EWSType_SensitivityChoicesType();
        $request->Items->CalendarItem->Sensitivity->_ = EWSType_SensitivityChoicesType::NORMAL;

        $request->SendMeetingInvitations = EWSType_CalendarItemCreateOrDeleteOperationType::SEND_TO_NONE;

        $request->Items->CalendarItem->LegacyFreeBusyStatus = $myEvent['free_busy_status'];

        $request->Items->CalendarItem->Categories = new EWSType_ArrayOfStringsType();
        $request->Items->CalendarItem->Categories->String = array(
            $myEvent['category']
        );

        $response = $this->ews->CreateItem($request);
        if ($response->ResponseMessages->CreateItemResponseMessage->ResponseClass == 'Success') {
            $replyArray = [];
            $replyArray['id'] = $response->ResponseMessages->CreateItemResponseMessage->Items->CalendarItem->ItemId->Id;
            $replyArray['change_key'] = $response->ResponseMessages->CreateItemResponseMessage->Items->CalendarItem->ItemId->ChangeKey;
        }
        return $replyArray;
    }

    /**
     * @param $myEvent
     */
    public function updateCalendarEvent($myEvent)
    {
        $startDate = new \DateTime($myEvent['start']);
        $endDate = new \DateTime($myEvent['end']);

        $request = new EWSType_UpdateItemType();
        $request->ConflictResolution = 'AlwaysOverwrite';
        $request->SendMeetingInvitationsOrCancellations = 'SendOnlyToAll';
        $request->ItemChanges = array();

        $change = new EWSType_ItemChangeType();
        $change->ItemId = new EWSType_ItemIdType();
        $change->ItemId->Id = $myEvent['source_id'];
        $change->ItemId->ChangeKey = $myEvent['change_key'];

        //Update Subject Property
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'item:Subject';
        $field->CalendarItem = new EWSType_CalendarItemType();
        $field->CalendarItem->Subject = $myEvent['subject'];
        $change->Updates->SetItemField[] = $field;

        //Update Start Property
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'calendar:Start';
        $field->CalendarItem = new EWSType_CalendarItemType();
        $field->CalendarItem->Start = $startDate->format('c');
        $change->Updates->SetItemField[] = $field;

        /*
        // Add some categories to the event.
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'calendar:Categories';
        $field->CalendarItem->Categories = new EWSType_ArrayOfStringsType();
        $field->CalendarItem->Categories->String = array('Testing', 'php-ews');
        $change->Updates->SetItemField[] = $field;
        */

        //Update End Property
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'calendar:End';
        $field->CalendarItem = new EWSType_CalendarItemType();
        $field->CalendarItem->End = $endDate->format('c');
        $change->Updates->SetItemField[] = $field;

        //Update Body Property
        /**
         * For Now we will not handle bodies of Events
         */
        /*
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'item:Body';
        $field->CalendarItem = new EWSType_CalendarItemType();
        $field->CalendarItem->Body = new EWSType_BodyType();
        $field->CalendarItem->Body->BodyType = EWSType_BodyTypeType::TEXT;
        $field->CalendarItem->Body->_ = 'Body Text Goes Here';
        $change->Updates->SetItemField[] = $field;
        */

        //Update ReminderIsSet Property
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'item:ReminderIsSet';
        $field->CalendarItem = new EWSType_CalendarItemType();
        $field->CalendarItem->ReminderIsSet = true;
        $change->Updates->SetItemField[] = $field;

        //Update ReminderMinutesBeforeStart Property
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'item:ReminderMinutesBeforeStart';
        $field->CalendarItem = new EWSType_CalendarItemType();
        $field->CalendarItem->ReminderMinutesBeforeStart = 30;
        $change->Updates->SetItemField[] = $field;

        //Update LegacyFreeBusyStatus Property
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'calendar:LegacyFreeBusyStatus';
        $field->CalendarItem = new EWSType_CalendarItemType();
        /**
         * 'Busy', 'Free','Tentative', 'Away', 'OOF' (Working from somewhere else)
         */
        $field->CalendarItem->LegacyFreeBusyStatus = $myEvent['free_busy_status'];
        $change->Updates->SetItemField[] = $field;

        //Update Location Property
        /**
         * not modify Location
         */
        /*
        $field = new EWSType_SetItemFieldType();
        $field->FieldURI = new EWSType_PathToUnindexedFieldType();
        $field->FieldURI->FieldURI = 'calendar:Location';
        $field->CalendarItem = new EWSType_CalendarItemType();
        $field->CalendarItem->Location = 'Conference Room';
        $change->Updates->SetItemField[] = $field;
        */

        $request->ItemChanges[] = $change;

        //$response = $this->ews->UpdateItem($request);
        //var_dump($response);exit;
    }

    private function createEventArrayFromResponse($calendarItem)
    {
        $myEvent = [];
        $myEvent['id'] = $calendarItem->ItemId->Id;
        $myEvent['change_key'] = $calendarItem->ItemId->ChangeKey;
        $myEvent['start'] = date("Y-m-d H:i:s", strtotime($calendarItem->Start));
        $myEvent['end'] = date("Y-m-d H:i:s", strtotime($calendarItem->End));
        $myEvent['subject'] = $calendarItem->Subject;
        $myEvent['title'] = $calendarItem->Organizer->Mailbox->Name.' - '. $calendarItem->Subject;
        $myEvent['type'] = $calendarItem->CalendarItemType;
        $myEvent['organizer'] = $calendarItem->Organizer->Mailbox->Name;
        // It is used only on real Sync not available for v1
        //$myEvent['recurring'] = $calendarItem->IsRecurring;
        $myEvent['free_busy_status'] = $calendarItem->LegacyFreeBusyStatus;
        $myEvent['category'] = null;
        return $myEvent;
    }

    public function getEventCategory($itemId)
    {
        if (!is_null($completeEvent = $this->getEvent($itemId))) {
            return $completeEvent->Categories->String;
        }
        return null;
    }

    public function exceptionHandler($exception)
    {
        throw new \Exception($exception->getMessage());
    }
}
