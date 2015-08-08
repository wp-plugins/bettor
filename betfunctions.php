<?php
function getBets($post_id="", $show="all", $date_from=""){
    global $wpdb;
    $table_name = $wpdb->prefix . "bettor_bet";

    $where_post="";
    $where_show="";
    $where_date_from="";
    
    if($post_id!==""){
        $where_post=" AND id_beitrag=".$post_id." ";
    }
    
    if($show==="not_evaluated"){
        $where_show=" AND ergebnis is null ";
    }
    
        if($show==="evaluated"){
        $where_show=" AND ergebnis is not null ";
    }
    
    if($date_from!==""){
        $where_date_from=" AND DATE_FORMAT(anstoss,'%d.%m.%Y')<=".$date_from." ";
    }
    
    $sql="  SELECT 
                id,
                anstoss, 
                liga, 
                sportart, 
                spiel, 
                DATE_FORMAT(anstoss,'%d.%m.%Y %H:%i') as datum, 
                tip, 
                odd, 
                buchmacher, 
                einsatz, 
                ergebnis, 
                wertung,
                id_beitrag
            FROM 
                ".$table_name."
            WHERE 
                loeschen=0 
             AND
                post_status='publish'
            ".$where_post."
            ".$where_show."
            ".$where_date_from."
            ORDER BY 
                anstoss, id";

    $rows = $wpdb->get_results($sql);
    return $rows;
}

function auswerten($correct, $einsatz, $quote){
    $bilanz=null;
    $rf=null;
    
    switch ($correct) {
            case 1:
                    $bilanz=$einsatz*$quote-$einsatz;
                    $rf=__( 'correct', 'BettorPlugin' );
                    break;
            case 2:
                    $bilanz=-$einsatz;
                    $rf=__( 'false', 'BettorPlugin' );
                    break;
            case 3:
                    $bilanz=0;
                    $rf=__( 'Cashback', 'BettorPlugin' );
                    break;
            case 4:
                    $bilanz=(($einsatz/2)*$quote+$einsatz/2)-$einsatz;
                    $rf="50% ".__( 'correct', 'BettorPlugin' );
                    break;
            case 5:
                    $bilanz=($einsatz/-2);
                    $rf="50% ".__( 'false', 'BettorPlugin' );
                    break;
            case 6:
                    $bilanz=(($einsatz/2)*$quote)-$einsatz;
                    $rf="50-50";
                    break;		
            case 7:
                    $bilanz=$einsatz;
                    $rf=__( 'correct', 'BettorPlugin' );
                    break;	
            case 8:
                    $bilanz=-($einsatz*$quote-$einsatz);
                    $rf=__( 'false', 'BettorPlugin' );
                    break;							
    }
    $rueck['units']=$bilanz;
    $rueck['wertung']=$rf;
    return $rueck;
}

function buchmacher($id){
    $bookmakers=  getBookmakers();

    foreach($bookmakers as $value){
        $bookmaker[$value->id]=$value;
    }

    if($bookmaker[$id]->speciallink==""){
        if($bookmaker[$id]->picture!="" && wp_attachment_is_image($bookmaker[$id]->picture)){
            $image=wp_get_attachment_image_src( $bookmaker[$id]->picture, 'thumbnail' );
            $image="src='".$image[0]."'";
            $rueck[0]="<a href='".$bookmaker[$id]->link."'><img class='bettor_inline' title='".$bookmaker[$id]->bookmaker."' ".$image." alt='".$bookmaker[$id]->bookmaker."' width='80' height='18' /></a>";
        }else{
            $rueck[0]="<a href='".$bookmaker[$id]->link."'>".$bookmaker[$id]->bookmaker."</a>";
        }            
    }else{
        $rueck[0]=$bookmaker[$id]->speciallink;
    }
    $rueck[1]=$bookmaker[$id]->bookmaker;

    return $rueck;
}

function getSports($id=""){
    global $wpdb;
    $rows=array();
    
    $where_id="";
    if($id!=""){
        $where_id=" where id=".$id." ";
    }
    
    $table_name = $wpdb->prefix . "bettor_sports";
    
    $sql="  SELECT 
                id,
                sports,
                picture
            FROM 
                ".$table_name."
                ".$where_id."
            ORDER BY 
                sports, id";

    $rows = $wpdb->get_results($sql);
    return $rows;
}

function getBookmakers(){
    global $wpdb;
    $rows=array();

    $table_name = $wpdb->prefix . "bettor_bookmaker";
    
    $sql="  SELECT 
                id,
                bookmaker,
                picture,
                link,
                speciallink
            FROM 
                ".$table_name."
            ORDER BY 
                bookmaker, id";

    $rows = $wpdb->get_results($sql);
    return $rows;
}

function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
}

function get_image_sizes( $size = '' ) {

        global $_wp_additional_image_sizes;

        $sizes = array();
        $get_intermediate_image_sizes = get_intermediate_image_sizes();

        // Create the full array with sizes and crop info
        foreach( $get_intermediate_image_sizes as $_size ) {

                if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

                        $sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
                        $sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
                        $sizes[ $_size ]['crop'] = (bool) get_option( $_size . '_crop' );

                } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

                        $sizes[ $_size ] = array( 
                                'width' => $_wp_additional_image_sizes[ $_size ]['width'],
                                'height' => $_wp_additional_image_sizes[ $_size ]['height'],
                                'crop' =>  $_wp_additional_image_sizes[ $_size ]['crop']
                        );

                }

        }

        // Get only 1 size if found
        if ( $size ) {

                if( isset( $sizes[ $size ] ) ) {
                        return $sizes[ $size ];
                } else {
                        return false;
                }

        }

        return $sizes;
}

function bettorBetsBilanz($count_month){
    
    $bets=  getBets("","all",date('Y-m-01',strtotime("-".$count_month." month")));
    $bilanz=array();
    $bilanz['all']['units']=0;
    $bilanz['all']['count']=0;
    $bilanz['all']['einsatz']=0;
    $bilanz['all']['odd']=0;
    
    foreach($bets as $bet){
        $jahr=substr($bet->anstoss,0,4);
        $monat=substr($bet->anstoss,5,2);
        $bet_bilanz=  auswerten($bet->wertung, $bet->einsatz, $bet->odd);
        
        if(isset($bilanz[$jahr."-".$monat])){
            $bilanz[$jahr."-".$monat]['units']+=$bet_bilanz['units'];
            $bilanz[$jahr."-".$monat]['count']++;
        }else{
            $bilanz[$jahr."-".$monat]['units']=$bet_bilanz['units'];
            $bilanz[$jahr."-".$monat]['count']=1;
        }
        $bilanz['all']['units']+=$bet_bilanz['units'];
        $bilanz['all']['einsatz']+=$bet->einsatz;
        $bilanz['all']['odd']+=$bet->odd;
        $bilanz['all']['count']++;
    }
    
    return $bilanz;
}

function bettotGetMonthStatistic(){
    global $wpdb;
    
    $sql="  SELECT 
                date_format(anstoss, '%m.%Y') as zeit, 
                date_format(anstoss, '%Y') as year,
                sum(if(wertung=1,(odd-1)*einsatz, 
                if(wertung=2,-einsatz, 
                if(wertung=3,einsatz, 
                if(wertung=4,(einsatz/2)+(odd-1)*(einsatz/2), 
                if(wertung=5,einsatz/2, 
                if(wertung=6,(odd-1)*(einsatz/2), 
                if(wertung=7,einsatz, 
                if(wertung=8,(odd-1)*-einsatz,0))))))))) as gewinn, 
                count(wertung) as sum 
            FROM 
                ".$wpdb->prefix . "bettor_bet
            WHERE 
                wertung is not null 
            AND
                loeschen=0 
            AND
                post_status='publish'                
            GROUP BY 
                date_format(anstoss, '%m.%Y') 
            ORDER BY 
                zeit";
    
    $rows=$wpdb->get_results($sql);
    
    $balance=0;
    $erg=array();
    foreach($rows as $row){ 
        $erg[$row->zeit]['gewinn']=$row->gewinn;
        $balance=$balance+$row->gewinn;
        $erg[$row->zeit]['balance']=$balance;
        if($row->gewinn>0){
                $erg[$row->zeit]['farbe_win']="bettor_green";
        } else {
                $erg[$row->zeit]['farbe_win']="bettor_red";
        }
        if($balance>0){
                $erg[$row->zeit]['farbe_balance']="bettor_green";
        } else {
                $erg[$row->zeit]['farbe_balance']="bettor_red";
        }
        $erg[$row->zeit]['wetten']=$row->sum;
        $erg['year'][$row->year]=$row->year;
    }
    
    return $erg;
}

function bettorStatistikBlock(){
    global $wpdb;
    $query="SELECT 
                coalesce(round(sum(if(wertung=1,(odd-1)*einsatz,
                if(wertung=2,-einsatz,
                if(wertung=3,einsatz,
                if(wertung=4,(einsatz/2)+(odd-1)*(einsatz/2),
                if(wertung=5,einsatz/2,
                if(wertung=6,(odd-1)*(einsatz/2),
                if(wertung=7,einsatz,
                if(wertung=8,(odd-1)*-einsatz,0))))))))),2),0) as gewinn,
                coalesce(sum(if(wertung=1 or wertung=4 or wertung=5,1,0)),0) as richtig,
                coalesce(sum(if(wertung=2 or wertung=6 or wertung=8,1,0)),0) as falsch,
                coalesce(sum(if(wertung=3 or wertung=7,1,0)),0) as cashback,
                coalesce(sum(odd)/count(wertung),0) as d_odd,
                coalesce(sum(einsatz),0) as einsatz,
                count(wertung) as tipps
            FROM 
                ".$wpdb->prefix . "bettor_bet
            WHERE 
                wertung is not null
            AND 
                loeschen=0
            AND
                post_status='publish';";
    
    $rows=$wpdb->get_results($query);
    
    return $rows;
}

function show_tipp() {
    $rows = getBets(get_the_ID());

    $tipp = "";
    foreach ($rows as $row) {
        $liga = $row->liga;
        $sportart = $row->sportart;
        $spiel = $row->spiel;
        $datum = $row->datum;
        $tip = $row->tip;
        $odd = $row->odd;
        $einsatz = $row->einsatz;
        $buchmacher = $row->buchmacher;
        $ergebnis = $row->ergebnis;
        $wertung = $row->wertung;

        $bilanz = auswerten($wertung, $einsatz, $odd);

        $get_sportart = getSports();
        $option = get_option('bettor_options');

        foreach ($get_sportart as $value) {
            if ($sportart == $value->id) {
                $getimage = $value->picture;
                $text_sportart = $value->sports;
                break;
            }
        }

        $image_size = 'sportsimage';
        if (isset($option['picturesize']) && (in_array($option['picturesize'], get_intermediate_image_sizes()) || $option['picturesize'] == "full")) {
            $image_size = $option['picturesize'];
        }

        $classes = "alignleft";
        if (isset($option['picturealign']) && in_array($option['picturealign'], array("alignleft", "alignright"))) {
            $classes = $option['picturealign'];
        }

        if (wp_attachment_is_image($getimage) && (!isset($option['picture']) || $option['picture'] === 1)) {
            $image = wp_get_attachment_image_src($getimage, $image_size);
            $size = get_image_sizes($image_size);
            $show_sportart = "<img class='" . $classes . "' title='" . $text_sportart . "' src='" . $image[0] . "' alt='" . $text_sportart . "' width='" . $size['width'] . "' height='" . $size['height'] . "'/>";
        } else {
            $show_sportart = $text_sportart;
        }

        $textcolor = "";
        $option = get_option('bettor_options');
        if (isset($option['color_bet'])) {
            $textcolor = "color:" . $option['color_bet'] . ";";
        }

        $buchmacher_arr=buchmacher($buchmacher);

        $tipp.="<div style='" . $textcolor . "' class='bettor_game'>
                    <span style='font-size: 13px; font-weight: normal;'>
                        " . $show_sportart . "
                    </span>
                    <span>
                        " . $liga . "
                    </span>
                <br>
                    <span>
                        " . $spiel . " (" . $datum . ")
                    </span>
                <br>
                    <strong>
                    <span>
                        " . __('Tip', 'BettorPlugin') . ": " . $tip . "
                    </span>
                    </strong>
                </div> 
                <div class='bettor_tip'>
                    <span>
                        " . __('Odd', 'BettorPlugin') . ": " . $odd . " @" . $buchmacher_arr[0] . "
                    </span>
                <br>
                    <span>
                        " . __('Stake', 'BettorPlugin') . ": " . $einsatz . "
                    </span>
                <br>";

        if ($bilanz['units'] >= 0) {
            $farbe_gv = "<span class='bettor_green'>";
            $valuation = "<span class='bettor_green bettor_2size'>✓</span>";
            $win_loose = __('Win', 'BettorPlugin');
        } else {
            $farbe_gv = "<span class='bettor_red'>";
            $valuation = "<span class='bettor_red bettor_2size'>X</span>";
            $win_loose = __('Loose', 'BettorPlugin');
        }

        if (isset($wertung)) {
            $tipp.="<span class='bettor_result'> 
                            " . __('Result', 'BettorPlugin') . ": " . $ergebnis . " " . $valuation . "
                        </span>
                    <br>
                        <span class='bettor_winloose'>
                            " . $win_loose . ": " . $farbe_gv . $bilanz['units'] . " " . __('Units', 'BettorPlugin') . "</span>
                        </span>";
        } else {
            $tipp.="<span> 
                            " . __('Result', 'BettorPlugin') . ": -
                        </span>
                    <br>
                        <span> 
                            " . __('Win / Loose', 'BettorPlugin') . ": -
                        </span>";
        }

        $tipp.="</div>
                <hr>";
    }

    return $tipp;
}
