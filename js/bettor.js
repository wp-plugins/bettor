jQuery(document).ready(function () {
    if(jQuery("#bettor_stat").length>0){
        if (jQuery("#bettor_stat").width() < 768) {
            var none_colums = [1, 3, 4, 6, 7];
        } else if (jQuery("#bettor_stat").width() < 1024) {
            var none_colums = [1, 6, 7];
        } else {
            var none_colums = [];
        }
        jQuery("#bettor_stat").DataTable({
            responsive: true,
            "columnDefs": [
                {"orderable": false, "targets": [2, 3, 7, 9]},
                {"visible": false, "targets": [9]},
                {"orderData": [0, 1], "targets": 1},
                {className: "never", "targets": [9]},
                {className: "none", "targets": none_colums},
                {className: "all", "targets": [0, 2, 8]}
            ]
        });
    } 
    
    if(jQuery("#bettor_accordion").length>0){
        jQuery("#bettor_accordion").accordion();
    }
    
    if(jQuery("#bettorChart").length>0){
        var ctx = document.getElementById("bettorChart").getContext("2d");
        ctx.canvas.originalwidth = ctx.canvas.width;
        ctx.canvas.originalheight = ctx.canvas.height;
        
        var data = {
            'action': 'bettor_ajax_graph',
            'whatever': ajax_object.we_value      // We pass php values differently!
        };
        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_object.ajax_url, data, function(response) {
                erg = JSON.parse(response);
                bettorDrawGraph(erg.date, erg.sum);
        });
    }
});

function bettorDrawGraph(bet_date, bet_sum){
    var lineChartData = {
        labels: bet_date,
        datasets: [
            {
                label: "Bettor",
                fillColor: "rgba(220,220,220,0.2)",
                strokeColor: "rgba(220,220,220,1)",
                pointColor: "rgba(220,220,220,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: bet_sum
            }
        ]
    };

    // Get context with jQuery - using jQuery's .get() method.
    var ctx = jQuery("#bettorChart").get(0).getContext("2d");
    ctx.canvas.width = ctx.canvas.originalwidth;
    ctx.canvas.height = ctx.canvas.originalheight;
    // This will get the first returned node in the jQuery collection.
    var myNewChart = new Chart(ctx).Line(lineChartData, {
                responsive: true,
                maintainAspectRatio: true
        });

     setTimeout(function(){myNewChart.datasets[0].data = [50,50,50,60,60,60];}, 3000);        
}