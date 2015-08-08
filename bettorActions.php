<?php
function bettorActions() {
    include_once("betfunctions.php");

    $option = get_option('bettor_options');

    if (!isset($option['showBets']) || $option['showBets'] == 'top') {
        add_filter('the_content', 'insertTipp');
    } elseif ($option['showBets'] == 'bottom') {
        add_filter('the_content', 'insertTipp');
    } elseif ($option['showBets'] == 'no') {
        add_shortcode('bettor_bet', 'bettorBet_Shortcode');
    }

    add_shortcode('bettor_statistic_table', 'addStatistikTable');
    add_shortcode('bettor_statistic_years', 'addStatistikYears');
    add_shortcode('bettor_statistic_block', 'addStatistikBlock');
    add_shortcode('bettor_statistic_graph', 'addStatistikGraph');
    
    add_action( 'wp_ajax_bettor_ajax_graph', 'bettor_ajax_graph' );
    add_action( 'wp_ajax_nopriv_bettor_ajax_graph', 'bettor_ajax_graph' );
}

function bettor_ajax_graph(){
    global $wpdb;
    
    $sql="select date_format(anstoss,'%d.%m.%Y') as date_ts, 
            round(if(wertung=1,(odd-1)*einsatz,
                if(wertung=2,-einsatz,
                if(wertung=3,einsatz,
                if(wertung=4,(einsatz/2)+(odd-1)*(einsatz/2),
                if(wertung=5,einsatz/2,
                if(wertung=6,(odd-1)*(einsatz/2),
                if(wertung=7,einsatz,
                if(wertung=8,(odd-1)*-einsatz,0)))))))),2) as gewinn
                FROM 
                    ".$wpdb->prefix . "bettor_bet
                WHERE
                    wertung is not null
                AND 
                    loeschen=0
                AND
                    post_status='publish'    
                ORDER BY
                    anstoss";
    $rows=$wpdb->get_results($sql);
    $sum=0;
    $erg=array();
    foreach ($rows as $ergebnis) {
        $sum=$sum+$ergebnis->gewinn;
        $erg['date'][]=$ergebnis->date_ts;
        $erg['sum'][]=$sum;
    }
    echo json_encode($erg);
    wp_die();
}

function addStatistikGraph(){
    $ret='<div><canvas id="bettorChart" width="300" height="100"></canvas></div>';
    return $ret;
}

function addStatistikBlock(){
    $rows=  bettorStatistikBlock();
    
    $win_color="";
    $yield_color="";
    $bets=0;
    $units=0;
    $yield="0%";
    $win="<span class='bettor_green'>0</span>";
    $loose="<span class='bettor_red'>0</span>";
    $cashback="<span>0</span>";
    
    if(isset($rows[0])){
        $bets=$rows[0]->tipps;
        $win="<span class='bettor_green'>".$rows[0]->richtig."</span>";
        $loose="<span class='bettor_red'>".$rows[0]->falsch."</span>";
        $cashback=$rows[0]->cashback;
        $units=  number_format(round($rows[0]->gewinn,2),2,",",".");
        $yield=number_format(round(($rows[0]->gewinn/$rows[0]->einsatz * 100), 2),2,",",".")."%";
        
        if($yield>0){
            $yield_color="bettor_green";
        }else{
            $yield_color="bettor_red";
        }
        
        if($units>0){
            $win_color="bettor_green";
        }else{
            $win_color="bettor_red";
        }
    }
    
    
    $ret='
    <div class="bettor_statistik_block">
        <div class="bettor_bets bettor_rounded"><b>'.__("Bets", "BettorPlugin").'</b><br>'.$bets.'</div>
        <div class="bettor_win bettor_rounded"><b>'.__("Win", "BettorPlugin").'</b><br><span class="'.$win_color.'">'.$units.' Units</span></div>
        <div class="bettor_yield bettor_rounded"><b>'.__("Yield", "BettorPlugin").'</b><br><span class="'.$yield_color.'">'.$yield.'</span></div>
        <div class="bettor_winsloose bettor_rounded"><b>'.__("Bets / Win / Loose / Cashback", "BettorPlugin").'</b><br>'.$win.' / '.$loose.' / '.$cashback.'</div>
    </div>
<div class="bettor_clearing"></div>
<div class="bettor_winsloose_small bettor_rounded"><b>'.__("Bets / Win / Loose / Cashback", "BettorPlugin").'</b><br>'.$win.' / '.$loose.' / '.$cashback.'</div>';
    
    return $ret;
}

function addStatistikYears() {
    $currency = "€";
    $option = get_option('bettor_options');
    if (isset($option['currency'])) {
        $currency = $option['currency'];
    }
    if (isset($option['unit'])) {
        $units = $option['unit'];
    }
$ret='<div id="bettor_accordion">';

        $bet_stat = bettotGetMonthStatistic();
        foreach ($bet_stat['year'] as $key => $value) {
            $years[] = $key;
        }
        foreach ($years as $year) {
            $ret.='
            <h3>'.$year.'</h3>
            <div>
                <table border="1" id="test" style="display:block;">
                    <tr class="code">
                        <td class="bettor_td_1">
                            <div style="text-align: center;"><strong>'.__("Month", "BettorPlugin").'</strong></div></td>
                        <td class="bettor_td_2">
                            <div style="text-align: center;"><strong>'.__("Number of bets", "BettorPlugin").'</strong></div></td>
                        <td class="bettor_td_3">
                            <div style="text-align: center;"><strong>'.__("Win units", "BettorPlugin").'</strong></div></td>
                        <td class="bettor_td_4">
                            <div style="text-align: center;"><strong>'.__("Win in ", "BettorPlugin").' '. $currency.'</strong></div></td>
                        <td class="bettor_td_5">
                            <div style="text-align: center;"><strong>'.__("Balance in units", "BettorPlugin").'</strong></div></td>
                        <td class="bettor_td_6">
                            <div style="text-align: center;"><strong>'.__("Balance in ", "BettorPlugin").' '.$currency.'</strong></div></td>
                    </tr>';

                    for ($i = 1; $i <= 12; $i++) {
                        if(date('Ym',strtotime("-1 days"))>$year.str_pad($i, 2, 0, STR_PAD_LEFT)){
                            $bets = 0;
                            $win_units = 0;
                            $win_currency=0;
                            if(!isset($balance_units) || $balance_units==0){
                                $balance_units = 0;
                                $balance_currency=0;
                            }
                        }else{
                            $bets="";
                            $win_units="";
                            $balance_units="";
                            $win_currency="";
                            $balance_currency="";
                        }

                        $win_color = "";
                        $balance_color = "";
                        if (isset($bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year])) {
                            $bets = $bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year]['wetten'];
                            $win_units = number_format(round(($bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year]['gewinn']), 2), 2, ',', '.');
                            $win_currency = number_format(round(($bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year]['gewinn']* $units), 2), 2, ',', '.');
                            $balance_units = number_format(round(($bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year]['balance']), 2), 2, ',', '.');
                            $balance_currency = number_format(round(($bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year]['balance']* $units), 2), 2, ',', '.');
                            $win_color = $bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year]['farbe_win'];
                            $balance_color = $bet_stat[str_pad($i, 2, 0, STR_PAD_LEFT) . "." . $year]['farbe_balance'];
                        }
                        $ret.='
                        <tr>
                            <td class="bettor_td_1">'.str_pad($i, 2, 0, STR_PAD_LEFT).'.</td>
                            <td class="bettor_td_2">'.$bets.'</td>
                            <td class="bettor_td_3 '.$win_color.'">'.$win_units.'</td>
                            <td class="bettor_td_4 '.$win_color.'">'.$win_currency.'</td>
                            <td class="bettor_td_5 '.$balance_color.'">'.$balance_units.'</td>
                            <td class="bettor_td_6 '.$balance_color.'">'.$balance_currency.'</td>
                        </tr>';
                    }
                $ret.="</table></div>";
        }
    $ret.="</div>";

    return $ret;
}

function addStatistikTable() {
    $ret='
    <table id="bettor_stat" class="display" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>'.__("Kickoff", "BettorPlugin").'</th>
                <th>'.__("Sports", "BettorPlugin").'</th>
                <th>'.__("Game", "BettorPlugin").'</th>
                <th>'.__("Bet", "BettorPlugin").'</th>
                <th>'.__("Odd", "BettorPlugin").'</th>
                <th>'.__("Bookmaker", "BettorPlugin").'</th>
                <th>'.__("Units", "BettorPlugin").'</th>
                <th>'.__("Result", "BettorPlugin").'</th>
                <th>'.__("Balance", "BettorPlugin").'</th>
                <th></th>
            </tr>
        </thead>

        <tfoot>
            <tr>
                <th>'.__("Kickoff", "BettorPlugin").'</th>
                <th>'.__("Sports", "BettorPlugin").'</th>
                <th>'.__("Game", "BettorPlugin").'</th>
                <th>'.__("Bet", "BettorPlugin").'</th>
                <th>'.__("Odd", "BettorPlugin").'</th>
                <th>'.__("Bookmaker", "BettorPlugin").'</th>
                <th>'.__("Stake", "BettorPlugin").'</th>
                <th>'.__("Result", "BettorPlugin").'</th>
                <th>'.__("Balance", "BettorPlugin").'</th>
                <th></th>
        </tfoot>

        <tbody>';

            $bets = getBets("", "evaluated");
            foreach ($bets as $bet) {
                $sportsimage = "";
                $sports = getSports($bet->sportart);
                if (wp_attachment_is_image($sports[0]->picture)) {
                    $sportsimage = wp_get_attachment_image_src($sports[0]->picture, 'thumbnail');
                    $sportsimage = "<img height='25px' width='25px' src='" . $sportsimage[0] . "' alt='" . $sports[0]->sports . "' title='" . $sports[0]->sports . "'>";
                }
                $bilanz = auswerten($bet->wertung, $bet->einsatz, $bet->odd);
                if ($bilanz['units'] >= 0) {
                    $bet_color = "bettor_green";
                    $bet_sign = "✓";
                } else {
                    $bet_color = "bettor_red";
                    $bet_sign = "X";
                }
                
                $buchmacher_arr=buchmacher($bet->buchmacher);
                
                $ret.='
                <tr>
                    <td>'.date("d.m.y H:i", strtotime($bet->anstoss)).'</td>
                    <td>'.$sportsimage.'</td>
                    <td><b>'.$bet->liga.'</b><br><a href="'.get_permalink($bet->id_beitrag).'">'.$bet->spiel.'</a></td>
                    <td>'.$bet->tip.'</td>
                    <td>'.$bet->odd.'</td>
                    <td>'.$buchmacher_arr[0].'</td>
                    <td>'.$bet->einsatz.'</td>
                    <td>'.$bet->ergebnis.'</td>
                    <td><span class="'.$bet_color.'">'.$bilanz['units'] .' '. $bet_sign.'</span></td>
                    <td>'.$sports[0]->sports.'</td>
                </tr>';

            }
    $ret.='</tbody></table>';
    return $ret;
}

function bettorBet_Shortcode() {
    $tipp = show_tipp();
    return $tipp;
}

function insertTipp($content) {
    $tipp = show_tipp();
    $option = get_option('bettor_options');

    if (is_single()) {
        if (!isset($option['showBets']) || $option['showBets'] == 'top') {
            $content = $tipp . $content;
        } elseif ($option['showBets'] == 'bottom') {
            $content = $content . $tipp;
        }
    }

    if (has_shortcode($content, 'bettor_bet')) {
        bettorBet_Shortcode();
    }
    return $content;
}
