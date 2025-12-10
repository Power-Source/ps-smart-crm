<?php
    class CRM_customer{
	public $business_name="";
	public $email="";
	public $customerID="";

	public function set_customerbyID_doc($id){
		$c_table=WPsCRM_TABLE."clienti";
		$d_table=WPsCRM_TABLE."documenti";
		global $wpdb;

		$SQL=
			"SELECT C.ID_clienti, C.nome, C.cognome, C.ragione_sociale, C.email
			FROM $c_table AS C
			INNER JOIN $d_table AS D ON C.ID_clienti = D.fk_clienti
			WHERE D.id =$id";

		$data=$wpdb->get_row( $SQL ) ;

		$data->ragione_sociale !="" ? $this->business_name=$data->ragione_sociale : $this->business_name=$data->nome. " ". $data->cognome;
		$this->email=$data->email;
		$this->customerID=$data->ID_clienti;

	}

	public function set_customerbyID_agenda($id){
		$c_table=WPsCRM_TABLE."clienti";
		$a_table=WPsCRM_TABLE."agenda";
		global $wpdb;

		$SQL=
			"SELECT C.ID_clienti, C.nome, C.cognome, C.ragione_sociale, C.email
			FROM $c_table AS C
			INNER JOIN $a_table AS A ON C.ID_clienti = A.fk_clienti
			WHERE A.id_agenda =$id";

		$data=$wpdb->get_row( $SQL ) ;

		$data->ragione_sociale !="" ? $this->business_name=$data->ragione_sociale : $this->business_name=$data->nome. " ". $data->cognome;
		$this->email=$data->email;
		$this->customerID=$data->ID_clienti;

	}

	public function set_customerbyID_docRow($id){
		$c_table=WPsCRM_TABLE."clienti";
		$d_table=WPsCRM_TABLE."documenti";
		$r_table=WPsCRM_TABLE."documenti_dettaglio";
		global $wpdb;

		$SQL=
			"SELECT C.ID_clienti, C.nome, C.cognome, C.ragione_sociale, C.email
			FROM $c_table AS C
			WHERE C.ID_clienti IN (SELECT D.fk_clienti FROM $d_table AS D
									JOIN  $r_table AS R
								   ON
								   D.id = R.fk_documenti
								   WHERE R.id = $id
								  )";

		$data=$wpdb->get_row( $SQL ) ;
		$data->ragione_sociale !="" ? $this->business_name=$data->ragione_sociale : $this->business_name=$data->nome. " ". $data->cognome;
		$this->email=$data->email;
		$this->customerID=$data->ID_clienti;

	}

	public function set_customer($id){
		$table=WPsCRM_TABLE."clienti";
		global $wpdb;
		$SQL="SELECT C.ID_clienti, C.nome, C.cognome, C.ragione_sociale, C.email from $table AS C WHERE C.ID_clienti =$id";
		//echo $SQL;
		$data=$wpdb->get_row( $SQL ) ;
		$data->ragione_sociale !="" ? $this->business_name=$data->ragione_sociale : $this->business_name=$data->nome. " ". $data->cognome;
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