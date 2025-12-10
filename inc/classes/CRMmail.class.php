<?php
class CRM_mail{

	public $context="";
	public $ID_agenda="";
	public $ID_doc="";
	public $ID_docRow="";
    public $oCustomer;
	public $customerEmail;
	public $emailFrom;
	public $nameFrom;
	public $sendNow;
	public $customerID;
	public $status;
	public $emailArray=array();

	public function __construct($params)
	{
		global $wpdb;
		$this->status=0;
		if($params==""){
			die("Not enough paramenters given for class CRM_mail");
		}

		elseif(is_array($params) ){
			//isset($params['context']) ? $this->context = $params['context'] : $this->context = $this->setContext($this->ID_agenda);
			isset($params['ID_agenda']) ? $this->ID_agenda = $params['ID_agenda'] : $this->ID_agenda ="";
			isset($params['ID_doc']) ? $this->ID_doc = $params['ID_doc'] : $this->ID_doc = "";
			isset($params['ID_docRow']) ? $this->ID_docRow = $params['ID_docRow'] : $this->ID_docRow ="";
			isset($params['sendNow']) ? $this->sendNow = $params['sendNow'] : $this->sendNow = 0;
			isset($params['customerID']) ? $this->customerID = $params['customerID'] : $this->customerID =$customerID;
		}
		elseif (is_int($params)) {
			$this->ID_agenda=$params;
			//$this->context= $this->setContext($this->ID_agenda);
		}

		$options=get_option('CRM_general_settings');
		$fromMAIL=( isset($options['emailFrom']) && trim( $options['emailFrom'] ) !="" ) ? $options['emailFrom'] : get_bloginfo('admin_email') ;
		$fromHEADER=( isset($options['nameFrom'])  && trim( $options['nameFrom'] ) !="" ) ? $options['nameFrom'] : "WP smart CRM - ".get_bloginfo('name') ;
		$this->emailFrom=$fromHEADER.' <'.$fromMAIL.'>';

		$this->oCustomer=new CRM_customer();
		$this->oDocument=new CRM_document();

		if($this->ID_agenda!=""){
			$this->oDocument->set_documentbyID_agenda($this->ID_agenda);
			$this->oCustomer->set_customerbyID_agenda($this->ID_agenda);

		}

		if($this->ID_doc !=""){
			$this->oDocument->set_document($this->ID_doc);

		    $this->oCustomer->set_customerbyID_doc($this->ID_doc);

		}
		if($this->ID_docRow !=""){

			$this->oCustomer->set_customerbyID_docRow($this->ID_docRow);
			if($this->oDocument->set_documentbyID_row($this->ID_docRow) ) {

			}
		}
		if($this->customerID !="")
			$this->oCustomer->set_customer($this->customerID);

		$this->customerEmail=$this->oCustomer->get_customer()->email;
		//var_dump($this->customerID);
		//var_dump($params);
		//echo "IDAGENDA:".$this->ID_agenda."<br><br>";
		//echo "sono in __construct<br>";
		//echo "il cliente è:<br> ";
		//echo "nome: ".$this->oCustomer->get_customer()->name."<br>";
		//echo "Email:".$this->customerEmail."<br>";
		//echo "ID: ".$this->oCustomer->get_customer()->customer_id."<br><br>";
		////echo "La rule è: ".$this->get_activity($this->ID_agenda)->rule."<br>";
		//echo "Il documento è: ".$this->oDocument->get_document()->Type."<br><br>";
		//echo "L'importo è: ".$this->oDocument->get_document()->Gross;
		//echo "Conto righe documento:".count($this->oDocument->get_document_rows() );

		//if(count($this->oDocument->get_document_rows() )>0 )
		//    foreach($this->oDocument->get_document_rows() as $row)
		//        {
		//            echo $row['rowNetAmount']."--".$row['rowSKU'];
		//        }
		//self::CRM_queue($this->ID_agenda,$this->get_activity($this->ID_agenda)->rule);
		self::CRM_queue();
		//self::test_groups('editor');
		//echo "<pre style=\"margin-left:150px;max-width:80%\">";
		//echo "<br>Righe documento:<br>";
		//var_dump($this->oDocument->get_document_rows());
		//var_dump($this->emailArray);
		//echo "</pre>";
		//echo "<style>#adminmenuback{display:none;}</style>";
	}
	//END __construct

	/**
	 *
	 * Sets a context if not given based on Document type (if any)
	 *
	 **/
	public function setContext(){
		//if($this->context !="" )
		//    $oContext=$this->context ;
		//elseif($this->ID_agenda!="" ){
		//    $activity= $this->get_activity($this->ID_agenda);
		//    $oContext= $activity[0]->type;
		//}
		//return $context;
	}

	/**
	 *
	 * get and returns email of given customer ID; it prints it if second parameter=1
	 *
	 **/
	public function get_customerMail($id,$print=0){
		$table=WPsCRM_TABLE."clienti";
		global $wpdb;
		if($print ==1)
			echo $wpdb->get_var( "SELECT email FROM $table WHERE ID_clienti =$id" );
		return $wpdb->get_var( "SELECT email FROM $table WHERE ID_clienti =$id" );
	}

	/**
	 *
	 * get and returns name of given customer ID; it prints it if second parameter=1
	 *
	 **/
	public function get_customerName($id,$print=0){
		$table=WPsCRM_TABLE."clienti";
		global $wpdb;

		$data=$wpdb->get_row( "SELECT nome, cognome, ragione_sociale FROM $table WHERE ID_clienti =$id" ) ;

		$data->ragione_sociale !="" ? $name=$data->ragione_sociale : $name=$data->nome. " ". $data->cognome;

		if($print ==1)
			echo $name;

		return $name;
	}

	/**
	 *
	 * get and returns email of given WP user; it prints it if second parameter=1
	 *
	 **/
	public function get_userMail($userid, $print=0){
		$user=get_userdata( $userid );
		if($print ==1)
			print $user->user_email;
		return $user->user_email;
	}

	/**
	 *
	 * get a single activity by id from table "agenda"
	 * returns an object:
	 * -type
	 * -description
	 * -end_date
	 *-context
	 **/
	public function get_activity($id){
		global $wpdb;
		$culture=get_locale();
		$table=WPsCRM_TABLE."agenda";
		$SQL="select * from $table where id_agenda= $id";
		//echo $SQL;
		$datas=$wpdb->get_results( $SQL ) ;
		$activity=array();
		if(!empty($datas) ){
			foreach($datas as $activityData){
				//echo $activityData->id_agenda."<br><br><br>";
				$activita=array();
				$activita['id']=$id;
				switch ($activityData->tipo_agenda){
					case 1:
						$activita['context']="todo";
						break;
					case 2:
						$activita['context']="appointment";
						break;
					case 3:
						$activita['context']="expired payment";
						break;
					case 4:
						$activita['context']="purchase";
						break;
					case 5:
						$activita['context']="expiring service";
						break;
					case 6:
						$activita['context']="deadline";
						break;

				}
				//$activityData->tipo_agenda==2 ? $activita['type']="appointment" : $activita['type']="todo";
				$activita['subject']=$activityData->oggetto;
				$activita['annotation']=$activityData->annotazioni;
				$culture=="it_IT" ? $activita['date'] = date('d.m.Y',strtotime($activityData->start_date) ) : $activita['date']=$activityData->start_date;
				$activita['time'] = date('H:i',strtotime($activityData->start_date) );
				$activita['timestamp'] = strtotime($activityData->start_date);
				$activita['rule'] = (int) $activityData->fk_subscriptionrules ;
				$activita['customerID'] = (int) $activityData->fk_clienti ;
				$activita['documentID']= (int) $activityData->fk_documenti ;
                                $activita['timezone_offset']=$activityData->timezone_offset;
                                array_push($activity,$activita);
			}
		}



		//echo "<pre style=\"margin-left:150px\">";
		//echo "Activity: ";
		//var_dump($activity);
		//echo "</pre>";
		return $activity;
	}


	public function get_activitybyID_doc($id){
		$culture=get_locale();
		$a_table=WPsCRM_TABLE."agenda";
		$d_table=WPsCRM_TABLE."documenti";
		$r_table=WPsCRM_TABLE."subscriptionrules";
		global $wpdb;

		$SQL="SELECT * FROM $a_table AS A
				JOIN $d_table AS D
				ON (D.id=A.fk_documenti)
				WHERE D.id = $id";

		//echo $SQL;
		$datas=$wpdb->get_results( $SQL ) ;
		$activity=array();
		if(!empty($datas) ){
			foreach($datas as $activityData){
				//echo $activityData->id_agenda."<br><br><br>";
				$activita=array();
				$activita['id']=$activityData->id_agenda;
				switch ($activityData->tipo_agenda){
					case 1:
						$activita['context']="todo";
						break;
					case 2:
						$activita['context']="appointment";
						break;
					case 3:
						$activita['context']="expired payment";
						break;
					case 4:
						$activita['context']="purchase";
						break;
					case 5:
						$activita['context']="expiring service";
						break;
					case 6:
						$activita['context']="deadline";
						break;

				}
				//$activityData->tipo_agenda==2 ? $activita['type']="appointment" : $activita['type']="todo";
				$activita['subject']=$activityData->oggetto;
				$culture=="it_IT" ? $activita['date'] = date('d.m.Y',strtotime($activityData->end_date) ) : $activita['date']=$activityData->end_date;
				$activita['time'] = date('H:s',strtotime($activityData->end_date) );
				$activita['timestamp'] = strtotime($activityData->end_date);
				$activita['rule'] = (int) $activityData->fk_subscriptionrules ;
				$activita['customerID'] = (int) $activityData->fk_clienti ;
				$activita['documentID']= (int) $activityData->fk_documenti ;
				$activita['rowID']= (int) $activityData->fk_documenti_dettaglio ;
				//print_r($activita);echo "<br><br>";
				array_push($activity,$activita);
			}
		}
		//echo "<pre style=\"margin-left:150px\">";
		//echo "Activity: ";
		//var_dump($activity);
		//echo "</pre>";

		return $activity;
	}

	/**
	 *
	 * get and returns an array of notification steps taken from rule #ID
	 *
	 **/
	public function get_rule($id){
		$table=WPsCRM_TABLE."subscriptionrules";
		global $wpdb;
		$sql="SELECT steps from $table where ID=$id";
		$riga=$wpdb->get_row($sql,ARRAY_A);
		$rule=(object) array("id"=>$id);
		foreach($riga as $key=>$val){
			$steps=json_decode($val);
			$rule->$key=$val;
		}
		//echo "<pre style=\"margin-left:150px\">";
		//echo "Rule: ";
		//var_dump($steps);
		//echo "</pre>";
		return $rule;
	}

	/**
	 *
	 * returns an array of queries to
	 * be run in emails table
	 *
	 **/
	public function CRM_queue(){
		if ($this->ID_agenda !=""){
			$activities=$this->get_activity($this->ID_agenda);
		}
		elseif($this->ID_doc !=""){
			$activities=$this->get_activitybyID_doc($this->ID_doc);
		}
		$date_format = get_option( 'date_format' );
		$date_format = 'Y-m-d';
		if(!empty ($activities) ){
			foreach($activities as $activity){
				//var_dump($activities);
				//echo "ID esaminato:".$activity['rowID']."<br>";
				$steps=$this->get_rule($activity['rule']);
				$activityDate=$activity['timestamp'];
				//$activityTime=$activity['time'];
                                $activityTime_ts=$activityDate-($activity['timezone_offset']*60);
                                $activityTime=date('H:i',$activityTime_ts );
				$newSteps=json_decode($steps->steps);
				//echo"newsteps:". $newSteps."<br>";
				//if(! is_array((array) $steps) ) {
				//    //die('array required in function prepareQueue)');
				//    return;
				//}
				foreach($newSteps as $step)
				{

					if($step->mailToRecipients !=""){ //prepare email for users
						$userstring=$step->selectedUsers; //single users
						$users=explode(',',$userstring);
						foreach($users as $mailtoUser){
							$this->setQueue(
								$this->emailFrom,
								$this->get_userMail($mailtoUser),
								$this->setUserMailSubject($this->CRM_email_date($activityDate,0, $date_format)->pretty_date , $activityTime,  $activity['context'], $activity['rowID']),
								$this->setUserMailBody($this->CRM_email_date($activityDate,0, $date_format)->pretty_date, $activityTime, $activity['context'], $activity['rowID'], $activity['subject'], isset($activity['annotation']) ? $activity['annotation'] : '' ),
								$this->CRM_email_date($activityDate,$step->ruleStep, $date_format)->mysql_date,
								$activity['id'],
								$activity['documentID'],
								$activity['rowID'],
								$activity['customerID'],
                                                                array(),
                                                                true
								);
                            if ($this->sendNow==1)
                            {
    							$this->sendNotificationNow(
								    $this->emailFrom,
								    $this->get_userMail($mailtoUser),
								    $this->setUserMailSubject($this->CRM_email_date($activityDate,0, $date_format)->pretty_date , $activityTime,  $activity['context'], $activity['rowID']),
								    $this->setUserMailBody($this->CRM_email_date($activityDate,0, $date_format)->pretty_date, $activityTime, $activity['context'], $activity['rowID'], $activity['subject'], isset($activity['annotation']) ? $activity['annotation'] : ''),
								    $this->CRM_email_date($activityDate,$step->ruleStep, $date_format)->mysql_date,
								    $activity['id'],
								    $activity['documentID'],
								    $activity['rowID'],
									$activity['customerID'],
                                                                true
								);
                            }
						}

						$groupstring=$step->selectedGroups; //groups of users ( Administrator, editor etc)
						$groups=explode(',',$groupstring);

							foreach($groups as $usergroup){
								if(isset( $usergroup[0] ) && $usergroup[0] !="" ){
								$_users=get_users( array('role'=> $usergroup ) );
								foreach($_users as $mailuser){
									if( ! in_array($mailuser->ID, $users) ){
										$mailtoUser=$mailuser->ID;
										//echo $mailtoUser."---<br>";
										$this->setQueue(
										    $this->emailFrom,
										    $this->get_userMail($mailtoUser),
										    $this->setUserMailSubject($this->CRM_email_date($activityDate,0, $date_format)->pretty_date , $activityTime,  $activity['context'],$activity['rowID']),
										    $this->setUserMailBody($this->CRM_email_date($activityDate,0, $date_format)->pretty_date, $activityTime, $activity['context'],$activity['rowID'], $activity['subject'], $activity['annotation']),
										    $this->CRM_email_date($activityDate,$step->ruleStep, $date_format)->mysql_date,
										    $activity['id'],
										    $activity['documentID'],
										    $activity['rowID'],
											$activity['customerID']	,
                                                                                        array(),
                                                                                        true
										    );
									}
									if ($this->sendNow==1)
                                    {
            							$this->sendNotificationNow(

								            $this->emailFrom,
								            $this->get_userMail($mailtoUser),
								            $this->setUserMailSubject($this->CRM_email_date($activityDate,0, $date_format)->pretty_date , $activityTime,  $activity['context'], $activity['rowID']),
								            $this->setUserMailBody($this->CRM_email_date($activityDate,0, $date_format)->pretty_date, $activityTime, $activity['context'], $activity['rowID'], $activity['subject'], $activity['annotation']),
								            $this->CRM_email_date($activityDate,$step->ruleStep, $date_format)->mysql_date,
								            $activity['id'],
								            $activity['documentID'],
								            $activity['rowID'],
											$activity['customerID'],
                                                                        true
								        );
                                    }
								}
							}
						}

					}
					if($step->remindToCustomer !="" && $this->customerEmail !="" && $this->customerEmail !=NULL ){//prepare email to customer

						$this->setQueue(
							$this->emailFrom,
							$this->customerEmail,
							$this->setCustomerMailSubject($this->CRM_email_date($activityDate,0, $date_format)->pretty_date , $activityTime,$activity['context'],$activity['rowID']),
							$this->setCustomerMailBody($this->CRM_email_date($activityDate,0, $date_format)->pretty_date ,$activityTime, $activity['context'],$activity['rowID']),
							$this->CRM_email_date($activityDate,$step->ruleStep, $date_format)->mysql_date,
							$activity['id'],
							$activity['documentID'],
							$activity['rowID'],
							$activity['customerID']
							);
							if ($this->sendNow==1)
                            {
    							$this->sendNotificationNow(

								    $this->emailFrom,
									$this->customerEmail,
									$this->setCustomerMailSubject($this->CRM_email_date($activityDate,0, $date_format)->pretty_date , $activityTime,$activity['context'],$activity['rowID']),
									$this->setCustomerMailBody($this->CRM_email_date($activityDate,0, $date_format)->pretty_date ,$activityTime, $activity['context'],$activity['rowID']),
									$this->CRM_email_date($activityDate,$step->ruleStep, $date_format)->mysql_date,
									$activity['id'],
									$activity['documentID'],
									$activity['rowID'],
									$activity['customerID']
								);
                            }
					}
				}
			}
		}

	}

	/**
	 *generate the date in which email has to be sent,
	 *calculated in diff between avctivity date and rule step days in advance.
	 **/
	public function CRM_email_date($scheduledDate,$daysInAdvance,$format='Y-m-d'){

		$mailTimestamp=$scheduledDate - (int) $daysInAdvance * 86400;
		$_dates = (object) array('pretty_date' => WPsCRM_culture_date_format(date('Y-m-d',$mailTimestamp) ) , 'mysql_date'=>date('Y-m-d',$mailTimestamp) );
		return $_dates;

	}

	/**
	 *sets subject for email to customers
	 **/
	public function setCustomerMailSubject( $date, $time, $context, $row, $customSubject=""){
		$name=$this->oCustomer->get_business_name();
		$mailRow=$this->oDocument->get_documentRow($row);
		$options=get_option('CRM_business_settings');
		$business=$options['business_name'];
		switch($context){
			case "appointment":
				$subject= sprintf( __( 'Termin mit Mitarbeiter %1$s am %2$s', 'cpsmartcrm'), $business, $date);
				$subject.="\r\n";
				break;
			case "expiring service":
				$subject= sprintf( __( '%1$s: Auslaufender Dienst am %2$s', 'cpsmartcrm'), $business, $date);
				$subject.="\r\n";
				break;
			case "purchase":
				$subject= sprintf( __( '%1$s: Kauf am %2$s', 'cpsmartcrm'), $business, $date);
				$subject.="\r\n";
				break;
			case "deadline":
				$subject= sprintf( __( 'Neue Frist', 'cpsmartcrm'));
				$subject.="\r\n";
				break;
			case "generic":
				$subject= $customSubject;
				$subject.="\r\n";
				break;
		}
		return stripslashes($subject);
	}

	/**
	 *sets body for email to customers
	 **/
	public function setCustomerMailBody($date, $time, $context, $row, $customBody=""){
		$name=$this->oCustomer->get_business_name();
		$mailRow=$this->oDocument->get_documentRow($row);
		$options=get_option('CRM_business_settings');
		$business=$options['business_name'];
		switch($context){
			case "appointment":
				//$body="Gentile $name, vi ricordiamo l'appuntamento con Ns. personale in data: $date alle ore: $time\r\n";
				$body= sprintf( __( 'Hallo %1$s, wir erinnern Sie an den Termin mit unseren Mitarbeitern am %2$s bei %3$s', 'cpsmartcrm'), $name, $date, $time );
				$body.="\r\n";
				$body.=__( 'Mit freundlichen Grüßen', 'cpsmartcrm');
				$body.=PHP_EOL;
				break;
			case "expiring service":
//				$body="Gentile $name,\r\n";
//				$body.="$business vi ricorda  che il servizio : ".$mailRow['rowDescription'].$art." &egrave; in scadenza in data: $date\r\n";
				$body= sprintf( __( 'Hallo %1$s, %2$s erinnert Sie daran, dass der Dienst: %3$s am %4$s abläuft', 'cpsmartcrm'), $name, $business, $mailRow['rowDescription'], $date );
				break;
			case "purchase":
				//$body="$business: Acquisto in data: $date\r\n";
				$body= sprintf( __( '%1$s: Kauf am %2$s', 'cpsmartcrm'), $business, $date);
				$body.="\r\n";
				break;
			case "generic":
				$body= $customBody;
				$body.="\r\n";
				break;
		}
		return stripslashes($body);
	}

	/**
	 *sets subject for email to users
	 **/
	public function setUserMailSubject($date, $time, $context, $row){
		$name=$this->oCustomer->get_business_name();
		$id_fattura=$this->oDocument->get_document()->Progressivo;
		$mailRow=$this->oDocument->get_documentRow($row);
		switch($context){
			case "appointment":
				$subject= sprintf( __( 'Termin am %1$s mit %2$s', 'cpsmartcrm'), $date, $name );
				break;
			case "todo":
				$subject= sprintf( __( 'Todo am %s', 'cpsmartcrm'), $date);
				break;
			case "expiring service":
				$subject= sprintf( __( 'Auslaufender Dienst: %1$s auf %2$s', 'cpsmartcrm'), $mailRow['rowDescription'], $date );
				break;
			case "expired payment":
				$subject= sprintf( __( 'Die Zahlung für %1$s Rechnung Nr. %2$s ist abgelaufen', 'cpsmartcrm'), $date, $id_fattura);
				break;
			case "purchase":
				$subject= sprintf( __( 'Kauf am %1$s von %2$s', 'cpsmartcrm'), $date, $name );
				break;
			case "deadline":
				$subject= sprintf( __( 'Neue Frist', 'cpsmartcrm'));
				break;
		}
		return stripslashes($subject);
	}

	/**
	 *sets body for email to users
	 **/
	public function setUserMailBody( $date,$time, $context, $row, $subject, $annotations){
		$name=$this->oCustomer->get_business_name();
		$id_fattura=$this->oDocument->get_document()->Progressivo;
		$mailRow=$this->oDocument->get_documentRow($row);
		$amount=$this->oDocument->get_document()->Gross;

		$mailRow['rowSKU']!="" ? $art=" (ID #: ".$mailRow['rowSKU'].")" : $art="";

		switch($context){
			case "appointment":
				$body= sprintf( __( 'Wir erinnern Sie an den Termin mit %1$s am %2$s um %3$s', 'cpsmartcrm'), $name, $date, $time );
				break;
			case "todo":
				$body= sprintf( __( 'Wir erinnern Sie an die Aktivität %1$s am %2$s', 'cpsmartcrm'), $subject, $date);
				$body.="\r\n\r\n";
				$body.=$annotations;
			    break;
			case "deadline":
				$body= sprintf( __( 'Wir erinnern Sie an die Frist %1$s am %2$s', 'cpsmartcrm'), $subject, $date);
				$body.="\r\n\r\n";
				$body.=$annotations;
			    break;
			case "expiring service":
				$body= sprintf( __( 'Wir erinnern Sie daran, dass der Dienst: %1$s von %2$s am %3$s abläuft', 'cpsmartcrm'), $mailRow['rowDescription'].$art, $name, $date );
				break;
			case "expired payment":
				$body= sprintf( __( 'Wir erinnern Sie daran, dass die Zahlung von Rechnung Nr. %1$s, Betrag %2$d, am %3$s abgelaufen ist', 'cpsmartcrm'), $id_fattura, $amount, $date );
				break;
			case "purchase":
				$body= sprintf( __( 'Kaufen Sie am %1$s', 'cpsmartcrm'), $date );
				break;
		}
		return $body;
	}

	/**
	 * Prepares a single sql insert statement for inserting in emails table
	 * @param string $e_from
	 * @param string $e_to
	 * @param string $e_subject
	 * @param string $e_body
	 * @param DateTime $e_date
	 * @param int $ID_agenda
	 * @param int $ID_doc
	 * @param int $ID_docRow
	 * @param int $customerID
	 * @param array $attachments
	 **/
	public function setQueue($e_from,$e_to,$e_subject,$e_body, $e_date, $ID_agenda,$ID_doc,$ID_docRow, $customerID=0, $attachments=array(), $send_link=false){
		global $wpdb;
		is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
		if ( in_array( 'wp-smart-crm-advanced/wp-smart-crm-advanced.php', apply_filters( 'active_plugins', $filter) ) ) {
                    if ($send_link===true)
                    {
                        $url = admin_url();
			$link="admin.php?page=smart-crm&event=".$ID_agenda;
			$e_body.="\r\n".$url.$link;
                    }
		}

		$s_attachments=json_encode($attachments);
		$table=WPsCRM_TABLE."emails";
		$query=
		$wpdb->prepare(
			"INSERT INTO $table
			( e_from, e_to, e_subject, e_body,e_date,fk_agenda,fk_documenti,fk_documenti_dettaglio, fk_clienti, attachments)
			VALUES ( %s, %s, %s , %s, %s, %d, %d, %d, %d, %s)
			",
			$e_from,
			$e_to,
			$e_subject,
			$e_body,
			$e_date,
			$ID_agenda,
			$ID_doc,
			$ID_docRow,
			$customerID,
			$s_attachments
			);
		if($e_to !="")
			$wpdb->query($query);
		array_push($this->emailArray,(string) $query);

	}

	/**
	 * Sends immediate notification at activity creation
	 * @param string $e_from
	 * @param string $e_to
	 * @param string $e_subject
	 * @param string $e_body
	 * @param DateTime $e_date
	 * @param int $ID_agenda
	 * @param int $ID_doc
	 * @param int $ID_docRow
	 */
	public function sendNotificationNow($e_from,$e_to,$e_subject,$e_body, $e_date, $ID_agenda,$ID_doc,$ID_docRow, $customerID=0, $send_link=false){
		global $wpdb;
		is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
		if ( in_array( 'wp-smart-crm-advanced/wp-smart-crm-advanced.php', apply_filters( 'active_plugins', $filter) ) ) {
                    if ($send_link===true)
                    {
                        $url = admin_url();
			$link="admin.php?page=smart-crm&event=".$ID_agenda;
			$e_body.="\r\n".$url.$link;
                    }
		}

		$table=WPsCRM_TABLE."emails";
		if ($e_to)
		{
			$headers[] = 'From: '.$this->emailFrom . PHP_EOL;
		    if ( wp_mail( $e_to , $e_subject , $e_body , $headers ) )
		    {
		        $query=
		        $wpdb->prepare(
			        "INSERT INTO $table
			        ( e_from, e_to, e_subject, e_body,e_date,fk_agenda,fk_documenti,fk_documenti_dettaglio, fk_clienti, e_sent)
			        VALUES ( %s, %s, %s , %s, %s, %d, %d, %d, %d, %d)
			        ",
			        $e_from,
			        $e_to,
			        $e_subject,
			        $e_body,
			        $e_date,
			        $ID_agenda,
			        $ID_doc,
			        $ID_docRow,
					$customerID,
			        2
			        );
			    $wpdb->query($query);
		    }
		    else
		    {
		        $this->status=1;
		    }
		}
	}


	/**
	 * Summary of sendMailToCustomer
	 * @param string $e_from
	 * @param string $e_to
	 * @param string $e_subject
	 * @param string $e_body
	 * @param DateTime $e_date
	 * @param int $customerID
	 * @param array $headers
	 * @param array $attachments
	 */
	public function sendMailToCustomer($e_from,$e_to,$e_subject,$e_body, $e_date, $customerID, $headers="", $attachments = null ){
		global $wpdb;
		$s_attachments=json_encode($attachments);
		$table=WPsCRM_TABLE."emails";
		if ($e_to)
		{
			$headers[] = 'From: '.$this->emailFrom . PHP_EOL;
		    if ( wp_mail( $e_to , $e_subject , $e_body , $headers, $attachments ) )
		    {
		        $query=
		        $wpdb->prepare(
			        "INSERT INTO $table
			        ( e_from, e_to, e_subject, e_body,e_date,fk_agenda,fk_documenti,fk_documenti_dettaglio, e_sent, fk_clienti, attachments)
			        VALUES ( %s, %s, %s , %s, %s, %d, %d, %d, %d, %d, %s)
			        ",
			        $e_from,
			        $e_to,
			        $e_subject,
			        $e_body,
			        $e_date,
			        $ID_agenda,
			        $ID_doc,
			        $ID_docRow,
			        2,
					$customerID,
					$s_attachments
			        );
			    $wpdb->query($query);
		    }
		    else
		    {
		        $this->status=1;
		    }
		}
	}
	/**
	 * Summary of sendMailToUser
	 * @param string $e_from
	 * @param string $e_to
	 * @param string $e_subject
	 * @param string $e_body
	 * @param DateTime $e_date
	 * @param int $userID
	 * @param array $headers
	 * @param array $attachments
	 */
	public function sendMailToUser($e_from,$e_to,$e_subject,$e_body, $e_date, $userID, $headers="", $attachments=""){
		global $wpdb;
		$s_attachments=json_encode($attachments);
		$table=WPsCRM_TABLE."emails";
		if ($e_to)
		{
			$headers[] = 'From: '.$this->emailFrom . PHP_EOL;
      if ( wp_mail( $e_to , $e_subject ,$e_body , $headers, $attachments ) )
		    {
		        $query=
		        $wpdb->prepare(
			        "INSERT INTO $table
			        ( e_from, e_to, e_subject, e_body,e_date,fk_agenda,fk_documenti,fk_documenti_dettaglio, e_sent, fk_clienti, attachments)
			        VALUES ( %s, %s, %s , %s, %s, %d, %d, %d, %d, %d, %s)
			        ",
			        $e_from,
			        $e_to,
			        $e_subject,
			        $e_body,
			        $e_date,
			        $ID_agenda,
			        $ID_doc,
			        $ID_docRow,
			        2,
					$userID,
					$s_attachments
			        );
			    $wpdb->query($query);
		    }
		    else
		    {
		        $this->status=1;
		    }
		}
	}

	public function test_groups($groupstring){
		$groups=explode(',',$groupstring);
		foreach($groups as $usergroup){
			$users=get_users( array('role'     => $usergroup ) );
			foreach($users as $mailuser){
				echo "<pre style=\"margin-left:150px\">";
				var_dump($mailuser);
				echo "</pre>";
				echo "<style>#adminmenuback{display:none;}</style>";
				$mailtoUser=$mailuser->ID;
				echo $this->get_userMail($mailtoUser)."---";
				$this->setQueue(
				    $this->emailFrom,
				    $this->get_userMail($mailtoUser),
				    $this->setUserMailSubject($this->CRM_email_date($activityDate,0, $date_format) , $activityTime,  $activity['context'],$activity['rowID']),
				    $this->setUserMailBody($this->CRM_email_date($activityDate,0, $date_format), $activityTime, $activity['context'],$activity['rowID'], $activity['subject'], $activity['annotation']),
				    $this->CRM_email_date($activityDate,$step->ruleStep, $date_format),
				    $activity['id'],
				    $activity['documentID'],
				    $activity['rowID'],
					$activity['customerID']
				    );
			}
		}
	}
}
?>