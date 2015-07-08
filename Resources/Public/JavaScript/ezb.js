function ezbDetails() {
	$('a[href*=' / details / '').click(function(event) {
		event.preventDefault();
		var url = $(this).attr("href");
		openwindow(url, '', 'width=600,height=800,scrollbars,menubar');
	});
};

$(document).ready(function () {
	ezbDetails();
});

