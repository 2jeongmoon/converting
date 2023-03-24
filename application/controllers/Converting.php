<?php
defined("BASEPATH") OR exit("No direct script access allowed");

/**
 * Converting
 *
 * @property Base_model $Base_model
 * @property Common_model $Common_model
 * @property Account_model $Account_model
 * @property Booking_model $Booking_model
 * @property Member_model $Member_model
 * @property Property_model $Property_model
 * @property Converting_model $Converting_model
 */

class Converting extends Base_Controller
{
	private $platform;

	function __construct()
	{
		parent::__construct();

		date_default_timezone_set("Asia/Seoul");

		$this->load->library('Common');
		$this->load->helper("string");
		$this->load->model("Base_model");
		$this->load->model("Common_model");
		$this->load->model("Account_model");
		$this->load->model("Booking_model");
		$this->load->model("Member_model");
		$this->load->model("Property_model");
		$this->load->model("Converting_model");

		$this->platform = "platform";
	}

	function index() {
		$this->_head();

		// admin
		$member_info = $this->Member_model->getMember($_SESSION["sparkplus"]["user"]["seq"]);
		$seq_account = $_SESSION["sparkplus"]["user"]["seq_account"];
		$seq_account_group = $member_info["seq_account_group"];

		// admin check, account : SPARKPLUS, account_group : 플랫폼그룹
		if ($seq_account != "11ED695909459AA2A280029886A9FD64" || $seq_account_group != "2") {
			// Alert("권한없음", "/");
			// exit;
		}

		// booking_sp
		$arrBookingSp = $this->Converting_model->getBookingSparkplus();;

		// invoice_sp
		$arrInvoiceSp = $this->Converting_model->getInvoiceSparkplus();

		// invoice_detail_sp
		$arrInvoiceDetailSp = $this->Converting_model->getInvoiceDetailSparkplus();

		// 기존 예약건 데이터 가공
		foreach ($arrBookingSp as $k => $v) {
			$arrBookingSp[$k] = $v;

			// invoice_detail
			foreach ($arrInvoiceDetailSp as $key => $value) {
				if ($value["seq_item"] == $v["seq"]) {
					$arrBookingSp[$k]["invoice_detail"][] = $value;
					$seq_invoice = $value["seq_invoice"];
				}
			}

			// invoice
			foreach ($arrInvoiceSp as $k2 => $v2) {
				if ($v2["seq"] == $seq_invoice) {
					$arrBookingSp[$k]["invoice"] = $v2;
				}
			}
		}

		$res = array();
		$res["list"] = $arrBookingSp;
		$this->load->view("/admin/converting/list", $res);
	}

	// 미팅룸 예약내역 변환 Ajax
	function ajax_converting() {
		$data = $this->input->post();

		// return msg
		$responseData = [];

		try {
			// booking_sp
			$arrBookingSp = $this->Converting_model->getBookingSpBySeq($data["seq"]);

			// seq_property, seq_property_detail
			$propertyInfo = $this->Converting_model->getPropertyInfo($arrBookingSp["seq_property"]);
			if (empty($propertyInfo)) {
				throw new Exception("로그인 세션생성 실패", 400);
			} else {
				$arrReturnMsg[] = "platform.property 불러오기 성공 - {$propertyInfo["seq_property"]}, {$propertyInfo["seq_property_detail"]}";
			}

			// seq_conference_room matching data
			$seq_conference_room = $this->Converting_model->getSeqConferenceRoom($arrBookingSp["seq_conference_room"]);
			if (!$seq_conference_room) {
				throw new Exception("Converting Error - 매칭데이터 없음", 401);
			} else {
				$arrReturnMsg[] = "platform.seq_conference_room 불러오기 성공 - {$seq_conference_room}";
			}

			$payment_method = "CREDIT"; // default
			if ($arrBookingSp["payment_charge"] > 0 && $arrBookingSp["payment_credit"] <= 0) {
				$payment_method = "CHARGE";
			}

			if ($arrBookingSp["payment_charge"] <= 0 && $arrBookingSp["payment_credit"] > 0) {
				$payment_method = "CREDIT";
			}

			if ($arrBookingSp["payment_charge"] > 0 && $arrBookingSp["payment_credit"] > 0) {
				$payment_method = "MIX";
			}

			if ($arrBookingSp["payment_charge"] <= 0 && $arrBookingSp["payment_credit"] <= 0) {
				$payment_method = "FREE";
			}

			// insert booking
			$insertBooking["seq"] 					= hex2bin($data["seq"]);
			$insertBooking["seq_conference_room"] 	= hex2bin($seq_conference_room);
			$insertBooking["seq_property"] 			= hex2bin($propertyInfo["seq_property"]);
			$insertBooking["seq_property_detail"] 	= hex2bin($propertyInfo["seq_property_detail"]);
			$insertBooking["seq_member"] 			= hex2bin($arrBookingSp["seq_member"]);
			$insertBooking["title"] 				= $arrBookingSp["title"];
			$insertBooking["start_at"] 				= $arrBookingSp["start_at"];
			$insertBooking["end_at"] 				= $arrBookingSp["end_at"];
			$insertBooking["memo"] 					= $arrBookingSp["memo"]; // $arrBookingSp["memo"]; for test
			$insertBooking["payment_method"] 		= $payment_method;
			$insertBooking["payment_credit"] 		= $arrBookingSp["payment_credit"];
			$insertBooking["payment_charge"] 		= $arrBookingSp["payment_charge"];
			$insertBooking["convert_total_credit"] 	= $arrBookingSp["convert_total_credit"];
			$insertBooking["share_url"] 			= $arrBookingSp["share_url"];
			$insertBooking["state"] 				= $arrBookingSp["state"];
			$insertBooking["included_peak_time"] 	= $arrBookingSp["included_peak_time"];
			$insertBooking["seq_b2c_cbs"] 			= ""; // B2C 구독이력 key - 신규 계약건의 이용중인 구독이력 갱신 key 사용
			$insertBooking["credit_price"] 			= $arrBookingSp["credit_price"];
			$insertBooking["created_at"] 			= date("Y-m-d H:i:s", $arrBookingSp["created_at"]);
			$insertBooking["updated_at"] 			= $arrBookingSp["updated_at"] ? date("Y-m-d H:i:s", $arrBookingSp["updated_at"]) : "";
			$insertBooking["cancelled_at"] 			= $arrBookingSp["cancelled_at"] ? date("Y-m-d H:i:s", $arrBookingSp["cancelled_at"]) : "";
			$insertBooking["changed_at"] 			= $arrBookingSp["changed_at"] ? date("Y-m-d H:i:s", $arrBookingSp["changed_at"]) : "";

			// 이전된 예약내역 있는지 확인
			$arrBooking = $this->Converting_model->getBookingBySeq($data["seq"]);
			if (empty($arrBooking)) {
				// 없다면 insert
				$insertBookingResult = $this->Common_model->insert("{$this->platform}.booking", $insertBooking);
				if ($insertBookingResult == "error") {
					throw new Exception("Converting Error - Booking Insert 실패", 402);
				} else {
					$arrReturnMsg[] = "Booking Insert 성공[1] - {$data["seq"]}";
				}
			} else {
				$arrReturnMsg[] = "Booking Insert 성공[2] - {$data["seq"]}";
			}

			// seq_account
			$seq_account = $this->Converting_model->getSeqAccountMember($arrBookingSp["seq_member"]);
			if (!$seq_account) {
				throw new Exception("Converting Error - seq_account 불러오기 실패", 403);
			} else {
				$arrReturnMsg[] = "seq_account 불러오기 성공 - {$seq_account}";
			}

			// account s_type
			$s_type = $this->Converting_model->getAccountStype($seq_account);
			if (!$s_type) {
				throw new Exception("Converting Error - s_type 불러오기 실패", 400);
			} else {
				$arrReturnMsg[] = "s_type 불러오기 성공 - {$s_type}";
			}

			// seq_b2c_cbs B2C 구독이력 key
			$seq_b2c_cbs = "0";
			if ($s_type == "B2C") {
				$seq_b2c_cbs = $this->Converting_model->getSeqB2Ccbs($seq_account);
				if (!$seq_b2c_cbs) {
					throw new Exception("Converting Error - seq_b2c_cbs 불러오기 실패", 400);
				} else {
					$arrReturnMsg[] = "seq_b2c_cbs 불러오기 성공 - {$seq_b2c_cbs}";
				}
			}

			// invoice_sp
			$arrInvoiceSp = $this->Converting_model->getInvoiceSpBySeqBooking($data["seq"]);
			if (empty($arrInvoiceSp)) {
				throw new Exception("Converting Error - invoice_sp 불러오기 실패", 400);
			} else {
				$arrReturnMsg[] = "invoice_sp 불러오기 성공 - auto:{$arrInvoiceSp["auto"]}, auto_spend:{$arrInvoiceSp["auto_spend"]}, {$arrInvoiceSp["invoice"]}, {$arrInvoiceSp["refund_invoice"]}";
			}

			// seq_invoice
			$seq_invoice = $this->Converting_model->getInvoiceBySeqAccountYearMonth($seq_account, "2023", "01");
			if (!$seq_invoice) {
				throw new Exception("Converting Error - seq_invoice 불러오기 실패", 400);
			} else {
				$arrReturnMsg[] = "seq_invoice 불러오기 성공 - {$seq_invoice}";
			}

			// update invoice
			$updateInvoice["auto_spend"] 		= $arrInvoiceSp["auto_spend"];
			$updateInvoice["invoice"] 			= $arrInvoiceSp["invoice"];
			$updateInvoice["refund_invoice"] 	= $arrInvoiceSp["refund_invoice"];

			// invoice update where
			$updateInvoiceWhere["seq"] 	= hex2bin($seq_invoice);
			$updateInvoiceResult = $this->Common_model->update("{$this->platform}.invoice", $updateInvoice, $updateInvoiceWhere);
			if ($updateInvoiceResult == "error") {
				$arrResult = array("code" => "error", "msg" => "Converting Error - invoice Update 실패", "log" => $arrReturnMsg);
				echo json_encode($arrResult, true);
				exit;
			} else {
				$arrReturnMsg[] = "invoice Update 성공";
			}

			// invoice_detail_sp
			$arrInvoiceDetailSp = $this->Converting_model->getInvoiceDetailSpBySeqBooking($data["seq"]);
			if (empty($arrInvoiceDetailSp)) {
				throw new Exception("Converting Error - invoice_detail_sp 불러오기 실패", 400);
			} else {
				$arrReturnMsg[] = "invoice_detail_sp 불러오기 성공 - {$arrInvoiceDetailSp[0]["user_contents"]}";
			}

			// insert invoice_detail
			foreach ($arrInvoiceDetailSp as $k => $v) {
				$insertInvoiceDetail["seq_invoice"] 	= hex2bin($seq_invoice);
				$insertInvoiceDetail["item"] 			= "booking"; // 회의실 고정
				$insertInvoiceDetail["item_case"] 		= $v["item_case"];
				$insertInvoiceDetail["seq_item"] 		= hex2bin($arrBookingSp["seq"]); // seq_booking
				$insertInvoiceDetail["item_created_at"] = $arrBookingSp["created_at"];
				$insertInvoiceDetail["seq_member"] 		= hex2bin($arrBookingSp["seq_member"]);
				$insertInvoiceDetail["user_contents"] 	= $v["user_contents"];
				$insertInvoiceDetail["admin_contents"] 	= $v["admin_contents"];
				$insertInvoiceDetail["user_name"] 		= $v["user_name"];
				$insertInvoiceDetail["visible"] 		= $v["visible"];
				$insertInvoiceDetail["auto"] 			= "0";
				$insertInvoiceDetail["auto_spend"] 		= $v["auto_spend"] + $v["passing_spend"];
				$insertInvoiceDetail["invoice"] 		= $v["invoice"];
				$insertInvoiceDetail["refund_invoice"] 	= $v["refund_invoice"];
				$insertInvoiceDetail["credit_res"] 		= $v["auto_spend"] + $v["passing_spend"];
				$insertInvoiceDetail["charge_res"] 		= $v["invoice"];
				$insertInvoiceDetail["log_key"] 		= "PJD_CONVERTING_{$v["log_key"]}"; // $v["log_key"]
				$insertInvoiceDetail["seq_b2c_cbs"] 	= $seq_b2c_cbs; // 구독이력 seq

				$insertInvoiceDetailResult = $this->Common_model->insert("{$this->platform}.invoice_detail", $insertInvoiceDetail);
				if ($insertInvoiceDetailResult == "error") {
					throw new Exception("Converting Error - invoice_detail Insert 실패", 400);
				} else {
					$num = $k+1;
					$arrReturnMsg[] = "invoice_detail Insert 성공 - {$num}";
				}
			}

		} catch (Exception $e) {
            $arrResult = [
                "code" => $e->getCode(),
                "status" => "fail",
                "data" => $responseData,
                "error_code" => "",
                "error_message" => $e->getMessage()
            ];

            $this->output
                ->set_status_header($e->getCode())
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($arrResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                ->_display();
            exit;

        } finally {
            $this->output
                ->set_status_header("200")
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($arrResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                ->_display();
            exit;
        }
	}


	function get_microtime() {
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
}
