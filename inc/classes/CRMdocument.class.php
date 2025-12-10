<?php

class CRM_document{
	public $documentID;
	public $documentProgressivo;
	public $documentType;
	public $documentRowsID;
	public $documentRows;
	public $documentCustomerID;
	public $documentNet;
	public $documentTaxes;
	public $documentGross;
	public $error;

	public function set_document($id){
		$d_table=WPsCRM_TABLE."documenti";
		$r_table=WPsCRM_TABLE."documenti_dettaglio";
		global $wpdb;

		$SQL="SELECT D.id as ID_doc, D.progressivo, D.tipo, D.totale_imponibile, D.totale_imposta, D.totale as totale_doc, D.fk_clienti, R.* FROM $d_table AS D
			  INNER JOIN $r_table AS R ON D.id = R.fk_documenti
			  WHERE D.id =$id";
		//echo $SQL;
		$datas=$wpdb->get_results( $SQL ) ;
		if(!empty($datas) ){
		    foreach($datas as $docData){
		        $docData->tipo==1 ? $this->documentType="quotation" : $this->documentType="invoice";
		        $this->documentID=$docData->fk_documenti;
				$this->documentProgressivo=$docData->progressivo;
		        $this->documentCustomerID=$docData->fk_clienti;
		        $this->documentNet=$docData->totale_imponibile;
		        $this->documentTaxes=$docData->totale_imposta;
		        $this->documentGross=$docData->totale_doc;
		        $this->documentRowsID[]=(int)$docData->id;

				$this->documentRows[]=array('rowID'=>(int)$docData->id,'rowNetAmount'=>$docData->prezzo,'rowTaxes'=>$docData->iva,'rowGrosAmount'=>$docData->totale,'rowDescription'=>$docData->descrizione,'rowSKU'=>$docData->codice);
		    }
			//var_dump($this->documentRows);
		}
	}

	public function get_documentRow($id){
		if(count($this->documentRows > 0) ){
			foreach($this->documentRows as $rowData){

				if( $rowData['rowID']==$id ){

					return $rowData;
				}

			}
		}
	}

	public function set_documentbyID_agenda($id){
		$a_table=WPsCRM_TABLE."agenda";
		$d_table=WPsCRM_TABLE."documenti";
		$r_table=WPsCRM_TABLE."documenti_dettaglio";
		global $wpdb;


		$SQL="
		SELECT A.id_agenda, A.fk_clienti, A.fk_documenti, A.fk_documenti_dettaglio, A.fk_subscriptionrules, D.id as ID_doc, D.progressivo, D.tipo, D.totale_imponibile, D.totale_imposta, D.totale as totale_doc,R.*
		FROM  $a_table AS A
		JOIN  $d_table AS D
		ON A.fk_documenti=D.id
		JOIN  $r_table AS R
		ON D.id=R.fk_documenti
		WHERE A.id_agenda=$id";
		$datas=$wpdb->get_results( $SQL ) ;

		if(!empty($datas) ){
		    foreach($datas as $docData){
		        $docData->tipo==1 ? $this->documentType="quotation" : $this->documentType="invoice";
		        $this->documentID=$docData->fk_documenti;
				$this->documentProgressivo=$docData->progressivo;
		        $this->documentCustomerID=$docData->fk_clienti;
		        $this->documentNet=$docData->totale_imponibile;
		        $this->documentTaxes=$docData->totale_imposta;
		        $this->documentGross=$docData->totale_doc;
		        $this->documentRowsID[]=(int)$docData->id;

				$this->documentRows[]=array('rowID'=>(int)$docData->id,'rowNetAmount'=>$docData->prezzo,'rowTaxes'=>$docData->iva,'rowGrosAmount'=>$docData->totale,'rowDescription'=>$docData->descrizione,'rowSKU'=>$docData->fk_articoli);
		    }


		}

	}

	public function set_documentbyID_row($id){
		$r_table=WPsCRM_TABLE."documenti_dettaglio";
		$d_table=WPsCRM_TABLE."documenti";
		global $wpdb;
		$SQL="
			SELECT D.id as ID_doc, D.progressivo, D.tipo, D.totale_imponibile, D.totale_imposta, D.totale as totale_doc, D.fk_clienti, R.* FROM $d_table AS D JOIN $r_table as R ON R.fk_documenti = D.id
			WHERE R.fk_documenti IN
			(SELECT D1.id FROM $d_table AS D1 JOIN $r_table AS R1 ON D1.id=R1.fk_documenti WHERE R1.id=$id)
			";

		echo $SQL;

		$datas=$wpdb->get_results( $SQL ) ;
		foreach($datas as $docData){
			$data->tipo==1 ? $this->documentType="quotation" : $this->documentType="invoice";
			$this->documentID=$docData->ID_doc;
			$this->documentProgressivo=$docData->progressivo;
			$this->documentCustomerID=$docData->fk_clienti;
			$this->documentNet=$docData->totale_imponibile;
			$this->documentTaxes=$docData->totale_imposta;
			$this->documentGross=$docData->totale_doc;
			$this->documentRowsID[]=(int)$docData->id;

			$this->documentRows[]=array('rowID'=>(int)$docData->id,'rowNetAmount'=>$docData->prezzo,'rowTaxes'=>$docData->iva,'rowGrosAmount'=>$docData->totale,'rowDescription'=>$docData->descrizione,'rowSKU'=>$docData->fk_articoli);
		}
		//var_dump($this->documentRows);
		//return $this->documentRows;

	}

	public function get_document(){
		$document = new stdClass();
		$document->ID=$this->documentID;
		$document->Progressivo=$this->documentProgressivo;
		$document->Type=$this->documentType;
		$document->Customer= $this->documentCustomerID;
		$document->Net=$this->documentNet;
		$document->Gross=$this->documentGross;
		$document->Tax=$this->documentTaxes;
		$document->Rows=$this->documentRows;

		return $document;
	}

	public function get_document_rows(){
		return $this->documentRows;
	}
	public function get_document_type(){
		return $this->documentType;
	}
	public function get_documentCustomer(){
	    $documentCustomer=new CRM_customer();
	    $documentCustomer->set_customer($this->documentCustomerID);
	    return $documentCustomer->get_customer();
	}
	public function get_documentsbyCustomerID($id){
		global $wpdb;
		$SQL="SELECT * FROM ".WPsCRM_TABLE."documenti WHERE fk_clienti=".$id." ORDER BY data DESC";
		return $documents=$wpdb->get_results($SQL);
	}
}

