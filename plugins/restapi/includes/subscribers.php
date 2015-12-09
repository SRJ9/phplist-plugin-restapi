<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

class Subscribers {

    /**
     * <p>Get all the Subscribers in the system.</p>
		 * <p><strong>Parameters:</strong><br/>
		 * [order_by] {string} name of column to sort, default "id".<br/>
		 * [order] {string} sort order asc or desc, default: asc.<br/>
		 * [limit] {integer} limit the result, default 100.<br/>
		 * </p>
     * <p><strong>Returns:</strong><br/>
     * List of Subscribers.
     * </p>
     */
    static function subscribersGet( $order_by='id', $order='asc', $limit=100 ) {

				//getting optional values
				if ( isset( $_REQUEST['order_by'] ) && !empty( $_REQUEST['order_by'] ) ) $order_by = $_REQUEST['order_by'];
				if ( isset( $_REQUEST['order'] ) && !empty( $_REQUEST['order'] ) ) $order = $_REQUEST['order'];
				if ( isset( $_REQUEST['limit'] ) && !empty( $_REQUEST['limit'] ) ) $limit = $_REQUEST['limit'];

        Common::select( 'Users', "SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user ORDER BY $order_by $order LIMIT $limit;" );
    }

    /**
     * <p>Gets one given Subscriber.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * One Subscriber only.
     * </p>
     */
    static function subscriberGet( $id=0 ) {
        if ( $id==0 ) $id = $_REQUEST['id'];
        Common::select( 'User', "SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user WHERE id = $id;", true );
    }

    /**
     * <p>Gets one Subscriber via email address.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * One Subscriber only.
     * </p>
     */
    static function subscriberGetByEmail( $email = "") {
        if ( empty( $email ) ) $email = $_REQUEST['email'];
        Common::select( 'User', "SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user WHERE email = '$email';", true );
    }

    /**
     * <p>Adds one Subscriber to the system.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * [*confirmed] {integer} 1=confirmed, 0=unconfirmed.<br/>
     * [*htmlemail] {integer} 1=html emails, 0=no html emails.<br/>
     * [*rssfrequency] {integer}<br/>
     * [*password] {string} The password to this Subscriber.<br/>
     * [*disabled] {integer} 1=disabled, 0=enabled<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The added Subscriber.
     * </p>
     */
    static function subscriberAdd(){

        $sql = "INSERT INTO " . $GLOBALS['usertable_prefix'] . "user (email, confirmed, htmlemail, rssfrequency, password, passwordchanged, disabled, entered, uniqid) VALUES (:email, :confirmed, :htmlemail, :rssfrequency, :password, now(), :disabled, now(), :uniqid);";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $_REQUEST['email']);
            $stmt->bindParam("confirmed", $_REQUEST['confirmed']);
            $stmt->bindParam("htmlemail", $_REQUEST['htmlemail']);
            $stmt->bindParam("rssfrequency", $_REQUEST['rssfrequency']);
            $stmt->bindParam("password", $_REQUEST['password']);
            $stmt->bindParam("disabled", $_REQUEST['disabled']);
            
            // fails on strict
#            $stmt->bindParam("uniqid", md5(uniqid(mt_rand())));
            
            $uniq = md5(uniqid(mt_rand()));
            $stmt->bindParam("uniqid", $uniq);
            $stmt->execute();
            $id = $db->lastInsertId();
            $db = null;
            Subscribers::SubscriberGet( $id );
        } catch(\PDOException $e) {
            Response::outputError($e);
        }

    }

		/**
		 * <p>Updates one Subscriber to the system.</p>
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
    static function subscriberUpdate(){

        $sql = "UPDATE " . $GLOBALS['usertable_prefix'] . "user SET email=:email, confirmed=:confirmed, htmlemail=:htmlemail, rssfrequency=:rssfrequency, password=:password, passwordchanged=now(), disabled=:disabled WHERE id=:id;";

        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("id", $_REQUEST['id']);
            $stmt->bindParam("email", $_REQUEST['email'] );
            $stmt->bindParam("confirmed", $_REQUEST['confirmed'] );
            $stmt->bindParam("htmlemail", $_REQUEST['htmlemail'] );
            $stmt->bindParam("rssfrequency", $_REQUEST['rssfrequency'] );
            $stmt->bindParam("password", $_REQUEST['password'] );
            $stmt->bindParam("disabled", $_REQUEST['disabled'] );
            $stmt->execute();
            $db = null;
            Subscribers::SubscriberGet( $_REQUEST['id'] );
        } catch(\PDOException $e) {
            Response::outputError($e);
        }

    }

		/**
		 * <p>Deletes a Subscriber.</p>
		 * <p><strong>Parameters:</strong><br/>
		 * [*id] {integer} the ID of the Subscriber.<br/>
		 * </p>
		 * <p><strong>Returns:</strong><br/>
		 * The deleted Subscriber ID.
		 * </p>
		 */
    static function subscriberDelete(){

        $sql = "DELETE FROM " . $GLOBALS['usertable_prefix'] . "user WHERE id=:id;";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("id", $_REQUEST['id']);
            $stmt->execute();
            $db = null;
            Response::outputDeleted( 'Subscriber', $_REQUEST['id'] );
        } catch(\PDOException $e) {
            Response::outputError($e);
        }

    }
	
	/**
     * <p>Subscribers assigned to a list.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*user_id] {integer} the list-ID.
     * <p><strong>Returns:</strong><br/>
     * Array of subscribers assigned to a list.
     * </p>
	 * @author: Jose <jose@alsur.es>
     */
    static function subscribersList ( $list_id=0 ) {
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
    static function subscriberUpdateAttribute ($_data = array()) {
		
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
    static function subscriberUpdateAttributes () {
		$userid = $_REQUEST['userid'];
		
		$metadata = json_decode(stripslashes($_REQUEST['metadata']), true);
		foreach($metadata as $kd=>$vd){
			
			self::subscriberUpdateAttribute(array('userid' => $userid, 'attributeid' => $kd, 'value' => utf8_decode($vd)));
		}
        Subscribers::SubscriberGet( $userid);
        die(0);
    }

    static function blacklistedEmailInfo(){
        if ( !isset($_REQUEST['email']) ) {
            die('Parametro email no presente');
        }
        // $response = new Response();
        $email = $_REQUEST['email'];

        Common::select( 'Blacklisted',
            "SELECT added, `data` as reason FROM "
            . $GLOBALS['usertable_prefix'] . "blacklist INNER JOIN ".$GLOBALS['usertable_prefix'] ."blacklist_data"
            . " ON ".$GLOBALS['usertable_prefix'] . "blacklist.email=".$GLOBALS['usertable_prefix'] ."blacklist_data.email"
            ." WHERE ".$GLOBALS['usertable_prefix']."blacklist.email = '".$email."'", true );


    }
	
	

}
