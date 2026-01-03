<?php
    class CRM_customer{
	public $business_name="";
	public $email="";
	public $customerID="";

	public function set_customerbyID_doc($id){
		$c_table=WPsCRM_TABLE."kunde";
		$d_table=WPsCRM_TABLE."dokumente";
		global $wpdb;

		$SQL=
			"SELECT C.ID_kunde, C.name, C.nachname, C.firmenname, C.email
			FROM $c_table AS C
			INNER JOIN $d_table AS D ON C.ID_kunde = D.fk_kunde
			WHERE D.id =$id";

		$data=$wpdb->get_row( $SQL ) ;

		$data->firmenname !="" ? $this->business_name=$data->firmenname : $this->business_name=$data->name. " ". $data->nachname;
		$this->email=$data->email;
		$this->customerID=$data->ID_kunde;

	}

	public function set_customerbyID_agenda($id){
		$c_table=WPsCRM_TABLE."kunde";
		$a_table=WPsCRM_TABLE."agenda";
		global $wpdb;

		$SQL=
			"SELECT C.ID_kunde, C.name, C.nachname, C.firmenname, C.email
			FROM $c_table AS C
			INNER JOIN $a_table AS A ON C.ID_kunde = A.fk_kunde
			WHERE A.id_agenda =$id";

		$data=$wpdb->get_row( $SQL ) ;

		$data->firmenname !="" ? $this->business_name=$data->firmenname : $this->business_name=$data->name. " ". $data->nachname;
		$this->email=$data->email;
		$this->customerID=$data->ID_kunde;

	}

	public function set_customerbyID_docRow($id){
		$c_table=WPsCRM_TABLE."kunde";
		$d_table=WPsCRM_TABLE."dokumente";
		$r_table=WPsCRM_TABLE."dokumente_dettaglio";
		global $wpdb;

		$SQL=
			"SELECT C.ID_kunde, C.name, C.nachname, C.firmenname, C.email
			FROM $c_table AS C
			WHERE C.ID_kunde IN (SELECT D.fk_kunde FROM $d_table AS D
									JOIN  $r_table AS R
								   ON
								   D.id = R.fk_dokumente
								   WHERE R.id = $id
								  )";

		$data=$wpdb->get_row( $SQL ) ;
		$data->firmenname !="" ? $this->business_name=$data->firmenname : $this->business_name=$data->name. " ". $data->nachname;
		$this->email=$data->email;
		$this->customerID=$data->ID_kunde;

	}

	public function set_customer($id){
		$table=WPsCRM_TABLE."kunde";
		global $wpdb;
		$SQL="SELECT C.ID_kunde, C.name, C.nachname, C.firmenname, C.email from $table AS C WHERE C.ID_kunde =$id";
		//echo $SQL;
		$data=$wpdb->get_row( $SQL ) ;
		$data->firmenname !="" ? $this->business_name=$data->firmenname : $this->business_name=$data->name. " ". $data->nachname;
		$this->email=$data->email;
		$this->customerID=$id;
	}

	public function get_customer(){
		$customer = new stdClass();
		$customer->customer_id= $this->customerID;
		$customer->name = $this->business_name;
		$customer->email = $this->email;

		return $customer;
	}

	public function get_business_name(){
		return $this->business_name;
	}

	public function get_email(){
		return $this->email;
	}
	public function get_id(){

		return $this->$customerID;
	}

}