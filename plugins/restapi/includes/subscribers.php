<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

class Subscribers
{
    /**
     * Get all the Subscribers in the system.
     *
     * <p><strong>Parameters:</strong><br/>
     * [order_by] {string} name of column to sort, default "id".<br/>
     * [order] {string} sort order asc or desc, default: asc.<br/>
     * [limit] {integer} limit the result, default 100 (max 100)<br/>
     * [offset] {integer} offset of the result, default 0.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * List of Subscribers.
     * </p>
     */
    public static function subscribersGet($order_by = 'id', $order = 'asc', $limit = 100, $offset = 0)
    {

        if (isset($_REQUEST['order_by']) && !empty($_REQUEST['order_by'])) {
            $order_by = $_REQUEST['order_by'];
        }
        if (isset($_REQUEST['order']) && !empty($_REQUEST['order'])) {
            $order = $_REQUEST['order'];
        }
        if (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) {
            $limit = $_REQUEST['limit'];
        }
        if (isset($_REQUEST['offset']) && !empty($_REQUEST['offset'])) {
            $offset = $_REQUEST['offset'];
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $params = array (
            'order_by' => array($order_by,PDO::PARAM_STR),
            'order' => array($order,PDO::PARAM_STR),
            'limit' => array($limit,PDO::PARAM_INT),
            'offset' => array($offset,PDO::PARAM_INT),
        );

        Common::select('Subscribers', 'SELECT * FROM '.$GLOBALS['tables']['user']." ORDER BY :order_by :order LIMIT :limit OFFSET :offset;",$params);
    }

    /**
     * Get the total of Subscribers in the system.
     *
     * <p><strong>Parameters:</strong><br/>
     * none
     * </p>
     * <p><strong>Returns:</strong><br/>
     * Number of subscribers.
     * </p>
     */
    public static function subscribersCount()
    {
        Common::select('Subscribers', 'SELECT count(id) as total FROM '.$GLOBALS['tables']['user'],array(),true);
    }

    /**
     * Get one Subscriber by ID.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * One Subscriber only.
     * </p>
     */
    public static function subscriberGet($id = 0)
    {
        if ($id == 0) {
            $id = sprintf('%d',$_REQUEST['id']);
        }
        if (!is_numeric($id) || empty($id)) {
            Response::outputErrorMessage('invalid call');
        }

        $params = array(
            'id' => array($id,PDO::PARAM_INT),
        );
        Common::select('Subscriber', 'SELECT * FROM '.$GLOBALS['tables']['user']." WHERE id = :id;",$params, true);
    }

    /**
     * Get one Subscriber by email address.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * One Subscriber only.
     * </p>
     */
    public static function subscriberGetByEmail($email = '')
    {
        if (empty($email)) {
            $email = $_REQUEST['email'];
        }
        $params = array(
            'email' => array($email,PDO::PARAM_STR)
        );
        Common::select('Subscriber', 'SELECT * FROM '.$GLOBALS['tables']['user']." WHERE email = :email;",$params, true);
    }

    /**
     * Get one Subscriber by foreign key.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*foreignkey] {string} the foreign key of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * One Subscriber only.
     * </p>
     */
    public static function subscriberGetByForeignkey($foreignkey = '')
    {
        if (empty($foreignkey)) {
            $foreignkey = $_REQUEST['foreignkey'];
        }
        $params = array(
            'foreignkey' => array($foreignkey,PDO::PARAM_STR)
        );
        Common::select('Subscriber', 'SELECT * FROM '.$GLOBALS['tables']['user']." WHERE foreignkey = :foreignkey;",$params, true);
    }

    /**
     * Add one Subscriber.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * [*confirmed] {integer} 1=confirmed, 0=unconfirmed.<br/>
     * [*htmlemail] {integer} 1=html emails, 0=no html emails.<br/>
     * [*foreignkey] {string} Foreign key.<br/>
     * [*subscribepage] {integer} subscribe page to sign up to.<br/>
     * [*password] {string} The password for this Subscriber.<br/>
     * [*disabled] {integer} 1=disabled, 0=enabled<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The added Subscriber.
     * </p>
     */
    public static function subscriberAdd()
    {
        $sql = 'INSERT INTO '.$GLOBALS['tables']['user'].'
          (email, confirmed, foreignkey, htmlemail, password, passwordchanged, subscribepage, disabled, entered, uniqid)
          VALUES (:email, :confirmed, :foreignkey, :htmlemail, :password, now(), :subscribepage, :disabled, now(), :uniqid);';

        $encPwd = Common::encryptPassword($_REQUEST['password']);
        $uniqueID = Common::createUniqId();
        if (!validateEmail($_REQUEST['email'])) {
            Response::outputErrorMessage('invalid email address');
        }

        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam('email', $_REQUEST['email'], PDO::PARAM_STR);
            $stmt->bindParam('confirmed', $_REQUEST['confirmed'], PDO::PARAM_BOOL);
            $stmt->bindParam('htmlemail', $_REQUEST['htmlemail'], PDO::PARAM_BOOL);
            /* @@todo ensure uniqueness of FK */
            $stmt->bindParam('foreignkey', $_REQUEST['foreignkey'], PDO::PARAM_STR);
            $stmt->bindParam('password', $encPwd, PDO::PARAM_STR);
            $stmt->bindParam('subscribepage', $_REQUEST['subscribepage'], PDO::PARAM_INT);
            $stmt->bindParam('disabled', $_REQUEST['disabled'], PDO::PARAM_BOOL);
            $stmt->bindParam('uniqid', $uniqueID, PDO::PARAM_STR);
            $stmt->execute();
            $id = $db->lastInsertId();
            $db = null;
            self::SubscriberGet($id);
        } catch (\Exception $e) {
            Response::outputError($e);
        }
    }

    /**
     * Add a Subscriber with lists.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * [*foreignkey] {string} Foreign key.<br/>
     * [*htmlemail] {integer} 1=html emails, 0=no html emails.<br/>
     * [*subscribepage] {integer} subscribepage to sign up to.<br/>
     * [*lists] {string} comma-separated list IDs.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The added Subscriber.
     * </p>
     */
    public static function subscribe()
    {
        $sql = 'INSERT INTO '.$GLOBALS['tables']['user'].'
          (email, htmlemail, foreignkey, subscribepage, entered, uniqid)
          VALUES (:email, :htmlemail, :foreignkey, :subscribepage, now(), :uniqid);';

        $uniqueID = Common::createUniqId();
        $subscribePage = sprintf('%d',$_REQUEST['subscribepage']);
        if (!validateEmail($_REQUEST['email'])) {
            Response::outputErrorMessage('invalid email address');
        }

        $listNames = '';
        $lists = explode(',',$_REQUEST['lists']);

        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam('email', $_REQUEST['email'], PDO::PARAM_STR);
            $stmt->bindParam('htmlemail', $_REQUEST['htmlemail'], PDO::PARAM_BOOL);
            /* @@todo ensure uniqueness of FK */
            $stmt->bindParam('foreignkey', $_REQUEST['foreignkey'], PDO::PARAM_STR);
            $stmt->bindParam('subscribepage', $subscribePage, PDO::PARAM_INT);
            $stmt->bindParam('uniqid', $uniqueID, PDO::PARAM_STR);
            $stmt->execute();
            $subscriberId = $db->lastInsertId();
            foreach ($lists as $listId) {
                $stmt = $db->prepare('replace into '.$GLOBALS['tables']['listuser'].' (userid,listid,entered) values(:userid,:listid,now())');
                $stmt->bindParam('userid', $subscriberId, PDO::PARAM_INT);
                $stmt->bindParam('listid', $listId, PDO::PARAM_INT);
                $stmt->execute();
                $listNames .= "\n  * ".listname($listId);
            }
            $subscribeMessage = getUserConfig("subscribemessage:$subscribePage", $subscriberId);
            $subscribeMessage = str_replace('[LISTS]',$listNames,$subscribeMessage);

            $subscribePage = sprintf('%d',$_REQUEST['subscribepage']);
            sendMail($_REQUEST['email'], getConfig("subscribesubject:$subscribePage"), $subscribeMessage );
            addUserHistory($_REQUEST['email'], 'Subscription', 'Subscription via the Rest-API plugin');
            $db = null;
            self::SubscriberGet($subscriberId);
        } catch (\Exception $e) {
            Response::outputError($e);
        }
    }
    /**
     * Update one Subscriber.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the Subscriber.<br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * [*confirmed] {integer} 1=confirmed, 0=unconfirmed.<br/>
     * [*htmlemail] {integer} 1=html emails, 0=no html emails.<br/>
     * [*rssfrequency] {integer}<br/>
     * [*password] {string} The password to this Subscriber.<br/>
     * [*disabled] {integer} 1=disabled, 0=enabled<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The updated Subscriber.
     * </p>
     */
    public static function subscriberUpdate()
    {
        $sql = 'UPDATE '.$GLOBALS['tables']['user'].' SET email=:email, confirmed=:confirmed, htmlemail=:htmlemail WHERE id=:id;';

        $id = sprintf('%d',$_REQUEST['id']);
        if (empty($id)) {
            Response::outputErrorMessage('invalid call');
        }
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam('id', $id, PDO::PARAM_INT);
            $stmt->bindParam('email', $_REQUEST['email'], PDO::PARAM_STR);
            $stmt->bindParam('confirmed', $_REQUEST['confirmed'], PDO::PARAM_BOOL);
            $stmt->bindParam('htmlemail', $_REQUEST['htmlemail'], PDO::PARAM_BOOL);
            $stmt->execute();
            $db = null;
            self::SubscriberGet($id);
        } catch (\Exception $e) {
            Response::outputError($e);
        }
    }

    /**
     * Delete a Subscriber.
     *
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The deleted Subscriber ID.
     * </p>
     */
    public static function subscriberDelete()
    {
        $sql = 'DELETE FROM ' . $GLOBALS['tables']['user'] . ' WHERE id=:id;';
        try {
            if (!is_numeric($_REQUEST['id'])) {
                Response::outputErrorMessage('invalid call');
            }
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam('id', $_REQUEST['id'], PDO::PARAM_INT);
            $stmt->execute();
            $db = null;
            Response::outputDeleted('Subscriber', sprintf('%d', $_REQUEST['id']));
        } catch (\Exception $e) {
            Response::outputError($e);
        }
    }
     /**
     * @author: Jose <jose@alsur.es>
     */
    public static function subscribersList ( $list_id=0 ) {
        $response = new Response();
        if ( $list_id==0 ) {
            if (isset($_REQUEST['list_id'])) $list_id = $_REQUEST['list_id'];
            else die('Sin lista no hay subscriptores');
        }
        $sql = "SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user WHERE id IN (SELECT userid FROM " . $GLOBALS['table_prefix'] . "listuser WHERE listid=" . $list_id . ") ORDER BY id;";
        try {
            $db = PDO::getConnection();
            $stmt = $db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            $response->setData('Subscribers', $result);
            $response->output();
        } catch(\PDOException $e) {
            Response::outputError($e);
        }
        die(0);
    }

    /**
     * <p>modifico atributo a traves de la API.
     * {"9": "Pepito", "11": "Perez"}
     * No implementado en un primer momento.
     * </p>
     * @author: Jose <jose@alsur.es>
     */
    public static function subscriberUpdateAttribute ($_data = array()) {

        # no esta depurada llamando a esta funcion directamente por $_REQUEST.
        # if(!isset($_data)) $_data = $_REQUEST;

        $sql = "REPLACE INTO " . $GLOBALS['usertable_prefix'] . "user_attribute SET attributeid=:attributeid, userid=:userid, value=:value";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("attributeid", ($_data['attributeid']));
            $stmt->bindParam("userid", $_data['userid'] );
            $stmt->bindParam("value", $_data['value'] );
            $stmt->execute();
            $db = null;
        } catch(\PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>modifico json de atributos a traves de la API.
     * {"9": "Pepito", "11": "Perez"}
     * No implementado en un primer momento.
     * </p>
     * @author: Jose <jose@alsur.es>
     */
    public static function subscriberUpdateAttributes () {
        $userid = $_REQUEST['userid'];

        $metadata = json_decode(stripslashes($_REQUEST['metadata']), true);
        foreach($metadata as $kd=>$vd){

            self::subscriberUpdateAttribute(array('userid' => $userid, 'attributeid' => $kd, 'value' => utf8_decode($vd)));
        }
        Subscribers::SubscriberGet( $userid);
        die(0);
    }

    public static function blacklistedEmailInfo(){
        if ( !isset($_REQUEST['email']) ) {
            die('Parametro email no presente');
        }
        $response = new Response();
        $email = $_REQUEST['email'];

        $sql = "SELECT ". $GLOBALS['usertable_prefix'] . "blacklist.email, added, `data` as reason FROM "
            . $GLOBALS['usertable_prefix'] . "blacklist INNER JOIN ".$GLOBALS['usertable_prefix'] ."blacklist_data"
            . " ON ".$GLOBALS['usertable_prefix'] . "blacklist.email=".$GLOBALS['usertable_prefix'] ."blacklist_data.email"
            ." WHERE ".$GLOBALS['usertable_prefix']."blacklist.email = '".$email."'"
            . "
			UNION
			(
				SELECT email, null, 'Blacklist by profile user'
				FROM " . $GLOBALS['usertable_prefix'] . "user WHERE blacklisted=1 AND email = '".$email."'
			)
			LIMIT 1
			"

        ;


        try {
            $db = PDO::getConnection();
            $stmt = $db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if($result){
                $response->setData('blacklist', $result);
            } else {
                $result = array(
                    'email' => $email
                );
                $response->setData('whitelist', $result);
            }
            $db = null;
            $response->output();
        } catch(\PDOException $e) {
            Response::outputError($e);
        }
        die(0);


    }

    public static function subscriberMessages(){
        if ( !isset($_REQUEST['email']) && !isset($_REQUEST['userid']) ) {
            die('Parametro email/userid no presente');
        }
        if(isset($_REQUEST['userid'])){
            $userid = trim($_REQUEST['userid']);
            if(strlen($userid)==0 || strlen($userid)>10){

                Response::outputErrorMessage( 'Longitud de Parametro no apropiada' );

            }
        } elseif(isset($_REQUEST['email'])){

            $email = trim($_REQUEST['email']);
            if(strlen($email)==0 || strlen($email)>100){
                Response::outputErrorMessage( 'Longitud de Parametro no apropiada' );

            }
            $email = trim($_REQUEST['email']);

        }

        $sql = "SELECT
messageid,
`subject`,
userid,
`phplist_usermessage`.entered as entered,
`phplist_usermessage`.viewed as viewed,
`phplist_usermessage`.`status` as `status`,
email
FROM (`phplist_message`
INNER JOIN `phplist_usermessage` ON `phplist_message`.id=`phplist_usermessage`.messageid)
INNER JOIN `phplist_user_user` ON `phplist_usermessage`.userid = `phplist_user_user`.id

";
        if($userid) {
            $sql .= " WHERE userid = '{$userid}'";
        } elseif($email) {
            $sql .= " WHERE email = '{$email}'";
        }

        $sql .= " ORDER BY entered DESC";


        try {
            $response = new Response();
            $db = PDO::getConnection();
            $stmt = $db->query($sql);
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            $response->setData('messages', $result);
            $db = null;
            $response->output();
        } catch(\PDOException $e) {
            Response::outputError($e);
        }
        die(0);
    }

}
