<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

Class Converting_model extends Base_Model {

	private $platform;

	function __construct() {
		parent::__construct();

		$this->platform = "platform";
	}

	// sparkplus.booking
	function getBookingSparkplus(): ? array
	{
		$sql = "SELECT 
					HEX(bb.seq) AS seq,
					HEX(bb.seq_conference_room) AS seq_conference_room,
					HEX(bb.seq_property_origin) AS seq_property_origin,
					HEX(bb.seq_member) AS seq_member,
					bb.title,
					bb.start_at,
					bb.end_at,
					bb.memo,
					bb.payment_method,
					bb.payment_credit,
					bb.payment_charge,
					bb.convert_total_credit,
					bb.share_url,
					bb.created_at,
					bb.updated_at,
					bb.state,
					bb.cancelled_at,
					bb.changed_at,
					bb.included_peak_time,
					bb.other_property,
					bb.sale_channel,
					bb.seq_b2c_msh,
					bb.credit_price,
       				a.account_name,
       				m.name,
       				m.email,
       				b.title AS b_title,
					id.user_contents
				FROM sparkplus.booking AS bb 
				LEFT JOIN {$this->platform}.member m ON bb.seq_member = m.seq
				LEFT JOIN {$this->platform}.account a ON m.seq_account = a.seq
				LEFT JOIN {$this->platform}.booking b ON bb.seq = b.seq
				LEFT JOIN {$this->platform}.invoice_detail id ON id.seq_item = b.seq
				WHERE bb.start_at > UNIX_TIMESTAMP('2023-01-01')
				AND bb.state != 'CANCEL'
				GROUP BY bb.seq
				ORDER BY bb.seq DESC";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	// sparkplus.booking
	function getInvoiceSparkplus(): ? array
	{
		$sql = "SELECT * FROM sparkplus.invoice i
				LEFT JOIN sparkplus.invoice_detail id ON i.seq = id.seq_invoice 
				LEFT JOIN sparkplus.booking b ON id.seq_item = b.seq
				WHERE b.start_at > UNIX_TIMESTAMP('2023-01-01')
				AND b.state != 'CANCEL'
				GROUP BY i.seq";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	// sparkplus.booking
	function getInvoiceDetailSparkplus(): ? array
	{
		$sql = "SELECT * FROM sparkplus.invoice_detail id
				LEFT JOIN sparkplus.booking b ON id.seq_item = b.seq
				WHERE b.start_at > UNIX_TIMESTAMP('2023-01-01')
				AND b.state != 'CANCEL'";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	// 기존서비스 회의실 예약내역
	function getBookingSp(): ? array
	{
		$this->db->select("
            HEX(bs.seq) AS seq,
            HEX(bs.seq_conference_room) AS seq_conference_room,
            HEX(bs.seq_member) AS seq_member,
            HEX(bs.seq_property_origin) AS seq_property_origin,
            bs.title,
            bs.start_at,
            bs.end_at,
            bs.memo,
            bs.payment_method,
            bs.payment_credit,
            bs.payment_charge,
            bs.convert_total_credit,
            bs.share_url,
            bs.created_at,
            bs.updated_at,
            bs.state,
            bs.cancelled_at,
            bs.changed_at,
            bs.included_peak_time,
            bs.other_property,
            bs.sale_channel,
            HEX(bs.seq_b2c_msh) AS seq_b2c_msh,
            bs.credit_price,
            m.name,
            m.email,
            a.account_name
        ");
		$this->db->from("booking bs");
		$this->db->join("member m", "bs.seq_member = m.seq", "left");
		$this->db->join("account_match am", "m.seq_company = am.seq_company", "left");
		$this->db->join("account a", "am.seq_account = a.seq", "left");

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->result_array();
		}

		return $res;
	}

	// 기존서비스 회의실 예약내역 by seq
	function getBookingSpBySeq($seq): ? array
	{
		$sql = "SELECT 
					HEX(bb.seq) AS seq,
					HEX(bb.seq_conference_room) AS seq_conference_room,
					HEX(bb.seq_property_origin) AS seq_property_origin,
					HEX(bb.seq_member) AS seq_member,
					bb.title,
					bb.start_at,
					bb.end_at,
					bb.memo,
					bb.payment_method,
					bb.payment_credit,
					bb.payment_charge,
					bb.convert_total_credit,
					bb.share_url,
					bb.created_at,
					bb.updated_at,
					bb.state,
					bb.cancelled_at,
					bb.changed_at,
					bb.included_peak_time,
					bb.other_property,
					bb.sale_channel,
					HEX(bb.seq_b2c_msh) AS seq_b2c_msh,
					bb.credit_price,
       				HEX(cr.seq_property) AS seq_property 
				FROM sparkplus.booking AS bb 
				LEFT JOIN sparkplus.conference_room cr ON bb.seq_conference_room = cr.seq
				WHERE bb.start_at > UNIX_TIMESTAMP('2023-01-01')
				AND bb.state != 'CANCEL'
				AND bb.seq = UNHEX('$seq')
				ORDER BY bb.seq DESC";
		$query = $this->db->query($sql);
		return $query->row_array();
	}

	// 신규서비스 회의실 예약내역 by seq
	function getBookingBySeq($seq): ? array
	{
		$sql = "SELECT 
					HEX(bb.seq) AS seq,
					HEX(bb.seq_conference_room) AS seq_conference_room,
					HEX(bb.seq_member) AS seq_member,
					bb.title,
					bb.start_at,
					bb.end_at,
					bb.memo,
					bb.payment_method,
					bb.payment_credit,
					bb.payment_charge,
					bb.convert_total_credit,
					bb.share_url,
					bb.created_at,
					bb.updated_at,
					bb.state,
					bb.cancelled_at,
					bb.changed_at,
					bb.included_peak_time,
					HEX(bb.seq_b2c_cbs) AS seq_b2c_cbs,
					bb.credit_price,
       				HEX(cr.seq_property) AS seq_property 
				FROM {$this->platform}.booking AS bb 
				LEFT JOIN {$this->platform}.conference_room cr ON bb.seq_conference_room = cr.seq
				WHERE bb.start_at > UNIX_TIMESTAMP('2023-01-01')
				AND bb.state != 'CANCEL'
				AND bb.seq = UNHEX('$seq')
				ORDER BY bb.seq DESC";
		$query = $this->db->query($sql);
		return $query->row_array();
	}

	// 기존서비스 인보이스 내역
	function getInvoiceSp(): ? array
	{
		$this->db->select("
            HEX(seq) AS seq,
            HEX(seq_company) AS seq_company,
            year,
            month,
            auto,
            auto_spend,
            passing,
            passing_spend,
            lasting,
            lasting_spend,
            refund_invoice,
            invoice,
            visit_auto,
            visit_auto_spend,
            visit_passing,
            visit_passing_spend,
            visit_lasting,
            visit_lasting_spend,
            visit_refund_invoice,
            visit_invoice,
            created_at,
            updated_at,
            visit_booking_invoice,
            visit_booking_refund_invoice,
            my_branch_booking_invoice,
            my_branch_booking_refund_invoice,
            other_branch_booking_invoice,
            other_branch_booking_refund_invoice,
            contract_user_cnt,
            standard_user_cnt,
            contract_invoice,
            contract_refund_invoice,
            sindoh
        ");
		$this->db->from("sparkplus.invoice is");

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->result_array();
		}

		return $res;
	}

	// 기존서비스 인보이스 조회
	function getInvoiceSpBySeqBooking($seq_booking): ? array
	{
		$this->db->select("
            HEX(i.seq) AS seq,
            HEX(i.seq_company) AS seq_company,
            i.year,
            i.month,
            i.auto,
            i.auto_spend,
            i.passing,
            i.passing_spend,
            i.lasting,
            i.lasting_spend,
            i.refund_invoice,
            i.invoice,
            i.visit_auto,
            i.visit_auto_spend,
            i.visit_passing,
            i.visit_passing_spend,
            i.visit_lasting,
            i.visit_lasting_spend,
            i.visit_refund_invoice,
            i.visit_invoice,
            i.created_at,
            i.updated_at,
            i.visit_booking_invoice,
            i.visit_booking_refund_invoice,
            i.my_branch_booking_invoice,
            i.my_branch_booking_refund_invoice,
            i.other_branch_booking_invoice,
            i.other_branch_booking_refund_invoice,
            i.contract_user_cnt,
            i.standard_user_cnt,
            i.contract_invoice,
            i.contract_refund_invoice,
            i.sindoh
        ");
		$this->db->from("sparkplus.invoice i");
		$this->db->join("sparkplus.invoice_detail id", "i.seq = id.seq_invoice", "left");
		$this->db->join("sparkplus.booking b", "id.seq_item = b.seq", "left");
		$this->db->where("b.seq", "UNHEX('$seq_booking')", false);

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->row_array();
		}

		return $res;
	}

	// 기존서비스 인보이스 상세 내역
	function getInvoiceDetailSp(): ? array
	{
		$this->db->select("
            seq, HEX(seq_invoice) AS seq_invoice, item, item_code, item_case, HEX(seq_item) AS seq_item, item_created_at,
            HEX(seq_member) AS seq_member, user_contents, admin_contents, user_name, include, visible,
            auto, auto_spend, passing, passing_spend, lasting, lasting_spend, refund_invoice, invoice, credit_res, 
            charge_res, visit_auto,  visit_auto_spend,  visit_passing,  visit_passing_spend,  visit_lasting,  
            visit_lasting_spend, visit_refund_invoice, visit_invoice, visit_res,  visit_charge_res,  log_key, created_at, 
            before_month_credit, before_month_charge, before_month_visit_number, before_month_visit_charge, 
            seq_b2c_msh, other_branch_price_use,contract_auto_free_use,contract_auto_spend,
            contract_invoice, contract_refund_invoice,contract_booking_date, contract_free
        ");
		$this->db->from("sparkplus.invoice_detail ids");

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->result_array();
		}

		return $res;
	}

	// 기존 인보이스 상세 내역 by sparkplus.seq_booking
	function getInvoiceDetailSpBySeqBooking($seq_booking) {
		$this->db->select("
            seq, HEX(seq_invoice) AS seq_invoice, item, item_code, item_case, HEX(seq_item) AS seq_item, item_created_at,
            HEX(seq_member) AS seq_member, user_contents, admin_contents, user_name, include, visible,
            auto, auto_spend, passing, passing_spend, lasting, lasting_spend, refund_invoice, invoice, credit_res, 
            charge_res, visit_auto,  visit_auto_spend,  visit_passing,  visit_passing_spend,  visit_lasting,  
            visit_lasting_spend, visit_refund_invoice, visit_invoice, visit_res,  visit_charge_res,  log_key, created_at, 
            before_month_credit, before_month_charge, before_month_visit_number, before_month_visit_charge, 
            seq_b2c_msh, other_branch_price_use,contract_auto_free_use,contract_auto_spend,
            contract_invoice, contract_refund_invoice,contract_booking_date, contract_free
        ");
		$this->db->from("sparkplus.invoice_detail ids");
		$this->db->where("seq_item", "UNHEX('$seq_booking')", false);

		$query = $this->db->get();
		$res = array();

		if ($query->num_rows() > 0) {
			$res = $query->result_array();
		}

		return $res;
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

