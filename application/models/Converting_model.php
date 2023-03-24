<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

Class Converting_model extends Base_Model {

	private $platform;

	function __construct() {
		parent::__construct();

		$this->platform = "platform";
	}

	// match seq
	function getSeqConferenceRoom($seq_sp) {
		$this->db->select("HEX(seq_conference_room) AS seq_conference_room,");
		$this->db->from("{$this->platform}.conference_room_match crm");
		$this->db->where("seq_conference_room_sp", "UNHEX('$seq_sp')", false);

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->row_array();
		}

		return $res["seq_conference_room"];
	}

	// invoice data
	function getInvoiceBySeqAccountYearMonth($seq_account, $year, $month) {
		$this->db->select("HEX(seq) AS seq");
		$this->db->from("{$this->platform}.invoice i");
		$this->db->where("i.seq_account", "UNHEX('$seq_account')", false);
		$this->db->where("i.year", $year);
		$this->db->where("i.month", $month);

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->row_array();
		}

		return $res["seq"];
	}

	// seq_account, member
	function getSeqAccountMember($seq) {
		$this->db->select("HEX(seq_account) AS seq_account");
		$this->db->from("{$this->platform}.member");
		$this->db->where("seq", "UNHEX('$seq')", false);

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->row_array();
		}

		$this->db->last_query();

		return $res["seq_account"];
	}

	// seq_property, seq_property_detail
	function getPropertyInfo($seq_property_sp): ? array
	{
		$this->db->select("
			HEX(seq_property) AS seq_property,
			HEX(seq_property_detail) AS seq_property_detail
		");
		$this->db->from("{$this->platform}.property_match");
		$this->db->where("seq_property_sp", "UNHEX('$seq_property_sp')", false);

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->row_array();
		}

		return $res;
	}

	// account stype
	function getAccountStype($seq_account) {
		$this->db->select("s_type");
		$this->db->from("{$this->platform}.account");
		$this->db->where("seq", "UNHEX('$seq_account')", false);
		// $this->db->where("status", "USE");

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->row_array();
		}

		return $res["s_type"];
	}

	// seq_b2c_cbs
	function getSeqB2Ccbs($seq_account) {
		$this->db->select("cbs.seq AS seq_b2c_cbs");
		$this->db->from("{$this->platform}.account a");
		$this->db->join("{$this->platform}.contract_b2c cb", "a.seq = cb.seq_account", "left");
		$this->db->join("{$this->platform}.contract_b2c_subscribe cbs", "cb.seq = cbs.seq_contract_b2c", "left");
		$this->db->where("a.seq", "UNHEX('$seq_account')", false);
		// $this->db->where("a.status", "USE");
		$this->db->where("cb.status", "USE");
		$this->db->where("cbs.visible", "Y");

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->row_array();
		}

		return $res["seq_b2c_cbs"];
	}
}

