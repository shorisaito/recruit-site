$(function () {
    /* ページ切り替え（ICTサー） */
    $("#ict").click(function (event) {
        event.preventDefault();
        $("#ict, #lbd, .contents__ict, .contents__lbd").removeClass("lbd-show");
        $(".contents__ict").addClass("show");
    });

    /* ページ切り替え（LBDサー） */
    $("#lbd").click(function (event) {
        event.preventDefault();
        $("#ict, #lbd, .contents__ict, .contents__lbd").addClass("lbd-show");
        $(".contents__lbd").addClass("show");
    });

    // 詳細表示
    $(".newcomer-btn").on('click',function(){
        $('.mark').toggleClass('roll');
        $('.detail').toggleClass('open');
    })
});



