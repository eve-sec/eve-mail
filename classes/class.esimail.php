<?php
require_once('config.php');

use Swagger\Client\Configuration;
use Swagger\Client\ApiException;
use Swagger\Client\Api\MailApi;
use Swagger\Client\Api\AllianceApi;
use Swagger\Client\Api\CorporationApi;
use Swagger\Client\Api\CharacterApi;

require_once('classes/esi/autoload.php');
require_once('classes/class.esisso.php');

class ESIMAIL extends ESISSO
{

        public function __construct($characterID) {
            parent::__construct(null, $characterID);
        }

        public function sendMail($recipients, $subject, $body, $cspa = 0) {
            $rec_ary = array();
            foreach($recipients as $rec) {
                $temp = new \Swagger\Client\Model\PostCharactersCharacterIdMailRecipient();
                $temp->setRecipientId($rec['id']);
                $temp->setRecipientType($rec['type']);
                $rec_ary[]=$temp;
            }
            $mailapi = $this->getMailApi('esi-mail.send_mail.v1');
            $mail = new \Swagger\Client\Model\PostCharactersCharacterIdMailMail();
            $mail->setRecipients($rec_ary);
            $mail->setSubject($subject);
            $mail->setBody($body);
            $mail->setApprovedCost($cspa);
            try {
                $result = $mailapi->postCharactersCharacterIdMail($this->characterID, $mail, "tranquility");
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Mail not sent: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
            }
            return $result;
        }

        public function readMail($mailid) {
            $recipients = array();
            $subject = '';
            $body = '';
            $mailapi = $this->getMailApi();
            try {
                $mail = json_decode($mailapi->getCharactersCharacterIdMailMailId($this->characterID, $mailid, 'tranquility'), true);
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Mail could not be fetched: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
                return false;
            }
            return $mail;
        }

        public function markRead($mailid, $is_read = true) {
            $recipients = array();
            $subject = '';
            $body = '';
            $mailapi = $this->getMailApi();
            $contents = new \Swagger\Client\Model\PutCharactersCharacterIdMailMailIdContents();
            $contents->setRead($is_read);
            try {
                $mailapi->putCharactersCharacterIdMailMailId($this->characterID, $contents, $mailid, 'tranquility');
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Mail could not be updated: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
                return false;
            }
            return true;
        }

        public function deleteMail($mailid) {
            $mailapi = $this->getMailApi('esi-mail.organize_mail.v1');
            try {
                $mailapi->deleteCharactersCharacterIdMailMailId($this->characterID, $mailid, 'tranquility');
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Mail could not be deleted: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
                return false;
            }
            return true;
        }
         
        public function getMailApi($scope = 'esi-mail.read_mail.v1') {
            $accessToken = $this->getAccessToken($scope);
            $esiapi = new ESIAPI();
            $esiapi->setAccessToken($accessToken);
            $mailapi = $esiapi->getApi('Mail');
            return $mailapi;
        }

        public function getMailLabels() {
            $mailapi = $this->getMailApi();
            try {
                $labelfetch = $mailapi->getCharactersCharacterIdMailLabels($this->characterID, 'tranquility');
                $labels = array();
                $labels[0] = array('name' => 'All', 'unread' => $labelfetch->getTotalUnreadCount());
                foreach ($labelfetch->getLabels() as $label) {
                    $labels[$label->getLabelId()] = array('name' => $label->getName(), 'unread' => $label->getUnreadCount());
                }
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not retrieve Maillabels: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
                return null;
            }
            return $labels;
        }

        public function getMails($labels = null, $lastid = null, $pages = 4, $mlist = null) {
            $mailapi = $this->getMailApi();
            if ($labels == null) {
                try {
                    $labels = $mailapi->getCharactersCharacterIdMailLabels($this->characterID, 'tranquility');
                } catch (Exception $e) {
                    $this->error = true;
                    $this->message = 'Could not retrieve Maillabels: '.$e->getMessage().PHP_EOL;
                    $this->log->exception($e);
                    return null;
                }
            }
            $mails = array();
            $i = 0;
            try {
                do {
                    $mailfetch = $mailapi->getCharactersCharacterIdMail($this->characterID, 'tranquility', null, $labels, $lastid);
                    foreach ($mailfetch as $mail) {
                        $mails[] = json_decode($mail, true);
                    }
                    $lastid = end($mails)['mail_id'];
                    $i++;
                } while (count($mailfetch) && $i < $pages);
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Could not retrieve Mails: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
                return null;
            }
            if (!count($mails)) {
                return null;
            }
            $mailids = array();
            $mailids['alliance'] = array();
            $mailids['corporation'] = array();
            $mailids['character'] = array();
            $mailids['mailing_list'] = array();
            $mldict = $this->getMailingLists();
            foreach ($mails as $mail) {
                if(!in_array($mail['from'], (array)array_keys($mldict)) && !(isset(array_column($mail['recipients'], 'recipient_type', 'recipient_id')[$mail['from']]) && array_column($mail['recipients'], 'recipient_type', 'recipient_id')[$mail['from']] == 'mailing_list') ) {
                    $mailids['character'][]=$mail['from'];
                }
                foreach($mail['recipients'] as $recipient) {
                    $mailids[$recipient['recipient_type']][]=$recipient['recipient_id'];
                }
            }
            $dict = EVEHELPERS::esiIdsToNames(array_merge($mailids['alliance'], $mailids['corporation'], $mailids['character']));
            
            foreach ($mails as $i => $mail) {
                if (!isset($mail['is_read'])) {
                    $mails[$i]['is_read'] = false;
                }
                if (isset($dict[$mail['from']])) {
                    $mails[$i]['from_name'] = $dict[$mail['from']];
                } else {
                    if(!isset($mldict)) {
                        $mldict = $this->getMailingLists();
                    }
                    if (isset($mldict[$mail['from']])) {
                        $mails[$i]['from_name'] = $mldict[$mail['from']];
                    } else {
                        $mails[$i]['from_name'] = 'Unknown';
                    }
                }
                foreach($mail['recipients'] as $j => $recipient) {
                    if ($recipient['recipient_type'] == 'mailing_list') {
                        if(!isset($mldict)) {
                            $mldict = $this->getMailingLists();
                        }
                        if (isset($mldict[$recipient['recipient_id']])) {
                            $mails[$i]['recipients'][$j]['recipient_name'] = $mldict[$recipient['recipient_id']];
                        } else {
                            $mails[$i]['recipients'][$j]['recipient_name'] = 'Mailing list';
                        }
                    } elseif (isset($dict[$recipient['recipient_id']])) {
                        $mails[$i]['recipients'][$j]['recipient_name'] = $dict[$recipient['recipient_id']];
                    } else {
                        $mails[$i]['recipients'][$j]['recipient_name'] = 'Unknown';
                    }
                }
            }
            if (count($labels) == 1 && $labels[0] == 0 && $mlist != null) {
                $reduced = array();
                foreach ($mails as $mail) {
                    if(in_array($mlist, array_column($mail['recipients'],'recipient_id'))) {
                        $reduced[] = $mail;
                    }
                }
                return $reduced;
            }
            return $mails;
        }

        public function getMailingLists() {
            $mailapi = $this->getMailApi();
            $response = array();
            try {
                $result = $mailapi->getCharactersCharacterIdMailLists($this->characterID, 'tranquility');
            } catch (Exception $e) {
                $this->error = true;
                $this->message = 'Mail could not be updated: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
                return false;
            }
            foreach ($result as $list) {
                $response[$list->getMailingListId()] = $list->getName();
            }
            return $response;
        }

        public function getContacts() {
            if ($this->hasExpired()) {
                $this->verify();
            }
            $esiapi = new ESIAPI();
            $esiapi->setAccessToken($this->accessToken);
            $contactsapi = $esiapi->getApi('Contacts');
            $contacts = array();
            try {
                $response = array();
                $page = 1;
                do {
                    $contactspage = $contactsapi->getCharactersCharacterIdContacts($this->characterID, 'tranquility', $page);
                    if (count($contactspage)) {
                        $response = array_merge($response, $contactspage);
                    }
                    $page += 1;
                } while (count($contactspage));
                if (count($response)) {
                    $lookup = array();
                    foreach ($response as $contact) {
                        $id = $contact->getContactId();
                        $contacts[$id] = array();
                        $lookup[] = $id;
                        $contacts[$id]['id'] = $id;
                        $contacts[$id]['name'] = null;
                        $contacts[$id]['type'] = $contact->getContactType();
                        $contacts[$id]['watched'] = $contact->getIsWatched();
                        $contacts[$id]['standing'] = $contact->getStanding();
                    }
                    $universeapi = $esiapi->getApi('Universe');
                    $results = $universeapi->postUniverseNames(json_encode(array_unique($lookup)), 'tranquility');
                    foreach ($results as $r) {
                        if (isset($contacts[$r->getId()])) {
                            $contacts[$r->getId()]['name'] = $r->getName();
                        }
                    }
                }
            } catch (Exception $e) {
                $contacts = null;
                $this->error = true;
                $this->message = 'Could not retrieve Contacts: '.$e->getMessage().PHP_EOL;
                $this->log->exception($e);
            }
            return $contacts;
        }

}
