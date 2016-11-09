<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}

class Mbackend extends CI_Model{
	function __construct(){
		parent::__construct();
		//$this->auth = unserialize(base64_decode($this->session->userdata('44mpp3R4')));
		$this->db_remote='';
	}
	
	function getdata($type="", $balikan="", $p1="", $p2="",$p3="",$p4=""){
		$where = " WHERE 1=1 ";
		if($this->input->post('key')){
				$where .=" AND ".$this->input->post('kat')." like '%".$this->db->escape_str($this->input->post('key'))."%'";
		}
		if($this->input->post('db_flag')){
			$this->get_koneksi($this->input->post('db_flag'));
		}
		switch($type){
			
			case "data_login":
				$sql = "
					SELECT A.member_user,A.email_address,A.flag AS status,A.pwd,B.*,
					CONCAT(B.name_first,' ',B.name_last) AS nama_lengkap
					FROM tbl_member A
					LEFT JOIN tbl_registration B ON A.tbl_registration_id=B.id
					WHERE A.member_user = '".$p1."' OR A.email_address='".$p1."'
				";
			break;
			case "trans_buku_sekolah":
			case "trans_buku_umum":			
			case "trans_media_sekolah":			
			case "trans_media_umum":			
				if($type=='trans_buku_sekolah' || $type=='trans_media_sekolah')$where .=" AND B.jenis_pembeli='SEKOLAH'";
				if($type=='trans_buku_umum' || $type=='trans_media_umum')$where .=" AND B.jenis_pembeli='UMUM'";
				
				$sql="SELECT A.*,B.nama_sekolah,B.nama_lengkap 
					  FROM tbl_h_pemesanan A 
					  LEFT JOIN tbl_registrasi B ON A.tbl_registrasi_id=B.id 
					  ".$where."
					  ORDER BY A.tgl_order DESC";
				//echo $sql;
			break;
			default:
				if($balikan=='get'){$where .=" AND A.id=".$this->input->post('id');}
				$sql="SELECT A.* FROM ".$type." A ".$where;
				if($balikan=='get')return $this->db->query($sql)->row_array();
			break;
		}
		
		if($balikan == 'json'){
			if($this->input->post('db_flag')){
				return $this->get_json_grid($sql,$this->input->post('db_flag'));
			}else{
				return $this->get_json_grid($sql,'lokal');
			}
			
		}elseif($balikan == 'row_array'){
			if($this->input->post('db_flag')){
				$data=$this->db_remote->query($sql)->row_array();
				$this->db_remote->close();
				return $data;
			}
			else{
				return $this->db->query($sql)->row_array();
			}
		}elseif($balikan == 'result'){
			if($this->input->post('db_flag')){
				$data=$this->db_remote->query($sql)->result();
				$this->db_remote->close();
				return $data;
			}else{
				return $this->db->query($sql)->result();
			}
		}elseif($balikan == 'result_array'){
			if($this->input->post('db_flag')){
				$data=$this->db_remote->query($sql)->result_array();
				$this->db_remote->close();
				return $data;
			}else{
				return $this->db->query($sql)->result_array();
			}
		}
		
	}
	function get_koneksi($flag){
		if($flag=='B')$this->db_remote=$this->load->database('buku',true);
		if($flag=='M')$this->db_remote=$this->load->database('media',true);
		$connected = $this->db_remote->initialize();
		if (!$connected) {
		   echo 'Koneksi Salah';exit;
		}
	}
	function get_json_grid($sql,$koneksi){
		$page = (integer) (($this->input->post('page')) ? $this->input->post('page') : "1");
		$limit = (integer) (($this->input->post('rows')) ? $this->input->post('rows') : "10");
		if($koneksi!='lokal'){
			$count = $this->db_remote->query($sql)->num_rows();
		}
		else{
			$count = $this->db->query($sql)->num_rows();
		}
		
		if( $count >0 ) { $total_pages = ceil($count/$limit); } else { $total_pages = 0; } 
		if ($page > $total_pages) $page=$total_pages; 
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if($start<0) $start=0;
		 		
		$sql = $sql . " LIMIT $start,$limit";
		if($koneksi!='lokal'){
			$data = $this->db_remote->query($sql)->result_array();
		}
		else{			
			$data = $this->db->query($sql)->result_array();  
		}
				
		if($data){
		   $responce = new stdClass();
		   $responce->rows= $data;
		   $responce->total =$count;
		   return json_encode($responce);
		}else{ 
		   $responce = new stdClass();
		   $responce->rows = 0;
		   $responce->total = 0;
		   return json_encode($responce);
		} 
	}
	function get_combo($type="", $p1="", $p2=""){
		switch($type){
			case "cl_kategori":
				$sql = "
					SELECT id, nama_kategori as txt
					FROM cl_kategori
				";
			break;
		}
		
		return $this->db->query($sql)->result_array();
	}
	
	function simpandata($table,$data,$sts_crud){ //$sts_crud --> STATUS NYEE INSERT, UPDATE, DELETE
		$this->db->trans_begin();
		if(isset($data['id'])){
			$id = $data['id'];
			unset($data['id']);
		}
		
		switch($table){
			case "reservation":
				$table="tbl_reservation";
			break;
			case "planning":
			case "planning_package":
				if($table=='planning_package')$table='tbl_execution_transaction_package';
				else $table='tbl_execution_transaction';
				if($sts_crud=='edit'){
					if(isset($data['flag'])){$data['finish_date']=date('Y-m-d H:i:s');}
				}
				if($sts_crud=='add'){$data['flag']='P';}
				//echo $sts_crud;exit;
				//print_r($_POST);exit;
			break;
			case "package":
				$table='tbl_package_header';
				if($sts_crud=='add'){
					if($data['tbl_services_id']==5){$data['flag_log_owner']=0;}
					else $data['flag_log_owner']=1;
				}
				if($sts_crud=='delete'){
					$this->db->delete('tbl_package_detil',array('tbl_package_header_id'=>$id));
				}
			break;
			case "package_item":
				$table='tbl_package_detil';
			break;
			
			case "services":
				$table='tbl_services';
				if($sts_crud=='add_new')$sts_crud='add';$data['flag']='F';
				if($sts_crud=='delete'){
					$sql="SELECT * FROM tbl_services where pid=".$id;
					$ex=$this->db->query($sql)->result_array();
					if(count($ex)>0){
						foreach($ex as $v){
							$this->db->delete('tbl_services',array('pid'=>$v['id']));
						}
					}
					$this->db->delete('tbl_services',array('pid'=>$id));
				}
				//print_r($data);exit;
			break;
			case "pricing":
				$table='tbl_pricing_services';
			break;
			case "property":
				$table='tbl_unit_member';
			break;
		}
		
		switch ($sts_crud){
			case "add":
				if($table!='tbl_registration'){
					$data['create_date'] = date('Y-m-d H:i:s');
					$data['create_by'] = $this->auth['nama_user'];
				}
				$this->db->insert($table,$data);
			break;
			case "edit":
				$this->db->update($table, $data, array('id' => $id) );
				
			break;
			case "delete":
				$this->db->delete($table, array('id' => $id));
			break;
		}
		
		if($this->db->trans_status() == false){
			$this->db->trans_rollback();
			return 'gagal';
		}else{
			 return $this->db->trans_commit();
		}
	}
	function set_flag($p1,$data){
		$this->db->trans_begin();
		$id=$data['id'];
		unset($data['id']);
		switch($p1){
			case "confirmation_pay":
				$table="tbl_payment_confirm";
				$sql="SELECT B.id as id_invoice
						FROM tbl_payment_confirm A
						LEFT JOIN tbl_header_transaction B ON A.tbl_transaction_id=B.id
						WHERE A.flag_transaction='I' AND A.id='".$id."'";
				$id_inv=$this->db->query($sql)->row_array();
				if(isset($id_inv['id_invoice'])){
					if($data['flag']=='C')$flag_inv='CP';
					else {
						$flag_inv='F';
						$data['confirm_kode']=$this->lib->uniq_id();
						$data['date_confirm']=date('Y-m-d H:i:s');
					}
					$sql="UPDATE tbl_header_transaction SET flag='".$flag_inv."' WHERE id=".$id_inv['id_invoice'];
					$this->db->query($sql);
				}
			break;
			case "confirmation_pay_pack":
				$table="tbl_payment_confirm";
				$sql="SELECT B.id as id_invoice
						FROM tbl_payment_confirm A
						LEFT JOIN tbl_transaction_package B ON A.tbl_transaction_id=B.id
						WHERE A.flag_transaction='P' AND A.id='".$id."'";
				$id_inv=$this->db->query($sql)->row_array();
				if(isset($id_inv['id_invoice'])){
					if($data['flag']=='C')$flag_inv='CP';
					else {
						$flag_inv='F';
						$data['confirm_kode']=$this->lib->uniq_id();
						$data['date_confirm']=date('Y-m-d H:i:s');
					}
					$sql="UPDATE tbl_transaction_package SET flag='".$flag_inv."' WHERE id=".$id_inv['id_invoice'];
					$this->db->query($sql);
				}
			break;
		}
		$this->db->update($table,$data,array('id'=>$id));
		
		if($this->db->trans_status() == false){
			$this->db->trans_rollback();
			return 'gagal';
		}else{
			 return $this->db->trans_commit();
		}
	}
	
}
