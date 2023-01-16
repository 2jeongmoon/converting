<style>
body:before{width:0 !important;}
body{padding-left:10px !important;}
.title_logo{display:none !important;}
.thead_th > div, .tbody_tr .cell_g > div{height:auto !important;}
.col_10{min-width:10%;}
.tabcont_box{padding-left:10px;}
.tbody_tr .cell_g{padding-right:0 !important;}
.thead_th{padding-right:0 !important;}
.thead_th > div, .tbody_tr .cell_g > div{white-space:nowrap;overflow:hidden;}
.header-container{text-align:center !important;}
</style>

<div class="content-container">
	<div class="header-wrap">
		<div class="header-container">
			<h3 class="title">HAPPY NEW YEAR</h3>
			<button type="button" class="btn-left" onclick="history.back();"></button>
		</div>
	</div>
	<form id="frm" name="frm" method="post" enctype="multipart/form-data">
		<div class="tabcont_box">
			<h4 class="title_cont">예약목록 <button type="button" class="btn_g on converting">CONVERTING CLICK</button></h4>
			<div id="spaceList">

				<div class="thead_th">
					<div class="col_3">no.</div>
					<div class="col_15">어카운트명</div>
					<div class="col_10">email</div>
					<div class="col_15">title</div>
					<div class="col_4">state</div>
					<div class="col_4">크레딧</div>
					<div class="col_4">충전</div>
					<div class="col_4">총크레딧</div>
					<div class="col_8">start_at</div>
					<div class="col_8">end_at</div>
					<div class="col_5">상태</div>
					<div class="col_20">내용</div>
				</div>

				<?php foreach ($list as $k => $v) { ?>
					<div class="tbody_tr booking_list" booking-row="<?=$k?>">
						<div class="cell_g booking_row" data-seq="<?=$v["seq"]?>" booking-row="<?=$k?>" converting="<?=$v["user_contents"] ? "Y" : "N"?>">
							<div class="col_3"><?=$k + 1?></div>
							<div class="col_15"><?=$v["account_name"] ? $v["account_name"] : "<span style='color:red;'>매칭된 어카운트 없음</span>"?></div>
							<div class="col_10"><?=$v["email"] ? $v["email"] : "<span style='color:red;'>매칭된 회원 없음</span>"?></div>
							<div class="col_15"><?=$v["title"]?></div>
							<div class="col_4"><?=$v["state"]?></div>
							<div class="col_4"><?=$v["payment_credit"]?></div>
							<div class="col_4"><?=$v["payment_charge"]?></div>
							<div class="col_4"><?=$v["convert_total_credit"]?></div>
							<div class="col_8"><?=date("Y-m-d H:i:s", $v["start_at"])?></div>
							<div class="col_8"><?=date("Y-m-d H:i:s", $v["end_at"])?></div>
							<div class="col_5 res_btn">
								<?php if ($v["user_contents"] != "") { ?>
									<span class="tag_g s_blue">완료</span>
								<?php } ?>
							</div>
							<div class="col_20 notice" style="text-align:left;">
							</div>
						</div>
					</div>
					<div class="tbody_tr history_list hide" log-row="<?=$k?>">
						<div class="cell_g" data-seq="<?=$v["seq"]?>">
							<div class="col_100" style="width:100% !important;text-align:left;line-height:32px;padding-left:10px;">
								<ul class="history"></ul>
							</div>
						</div>
					</div>
				<?php } ?>

			</div>
		</div>
	</form>
</div>
<script>
// 컨버팅 버튼
$(document).on("click", ".converting", function(e) {
	wait();

	$.each($(".booking_row"), function(i, v) {
		let rowNum = $(v).attr("booking-row");
		let seq = $(v).data("seq");
		let convertingYN = $(v).attr("converting");

		// converting 완료된 내역 진행안함
		if (convertingYN == "N") {
			$.ajax ({
				type: "POST",
				url: "/converting/ajax_converting",
				data: {"seq": seq},
				dataType : "json",
				success : function(res) {
					// 상태출력
					if (res.code == "success") {
						$(v).find(".res_btn").html("<span class=\"tag_g s_blue\">완료</span>");
						$(v).find(".notice").html(res.msg);
					} else {
						$(v).find(".res_btn").html("<span class=\"tag_g s_red\">실패</span>");
						$(v).find(".notice").html(res.msg);
					}

					// 로그출력
					if (res.log) {
						$.each(res.log, function(i, v) {
							$("[log-row=" + rowNum + "]").find(".history").append("<li>" + v + "</li>");
						});
					}
				},
				error : function(err) {
					$("[log-row=" + rowNum + "]").find(".history").append("<li>time out</li>");
					wait_close();
				}
			});
		}
	});

	wait_close();
});

// 로그펼치기
$(document).on("click", ".booking_list", function(e) {
	let row = $(this).attr("booking-row");
	if ($("[log-row=" + row + "]").hasClass("hide")) {
		$("[log-row=" + row + "]").removeClass("hide");
	} else {
		$("[log-row=" + row + "]").addClass("hide");
	}
});
</script>

<link rel="stylesheet" type="text/css" href="../../files/css/select2.min.css">
<script src="../../files/js/select2.min.js"></script>
