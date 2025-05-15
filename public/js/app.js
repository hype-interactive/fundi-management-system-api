//active navbar behavior
$(document).ready(function () {
    var current = location.pathname;
    $(".nav-tabs li a").each(function () {
        $(".nav-item").find(".active").removeClass("active");
        var $this = $(this);
        if ($this.attr("href") == current) {
            $this.parent().addClass("active");
        }
        if (
            current == "/administration" ||
            current == "/operators" ||
            current == "/legacy-data"
        ) {
            $(".management").addClass("active");
        }
    });
});
