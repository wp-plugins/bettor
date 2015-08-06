<?php
function bettorAdminEditor(){
    add_action( 'add_meta_boxes', 'bettorPostBet' );
    add_action( 'save_post', 'bettorPostBet_save' );
}

function bettorPostBet() {
    add_meta_box(
            'bettorBets',
             __("Post your Bets!","BettorPlugin"),
            'bettorPostBet_callback',
            'post'
    );
}

function bettorPostBet_callback( $post ) {
        include_once("betfunctions.php");
	// Add a nonce field so we can check for it later.
	wp_nonce_field( 'bettorPostBet', 'bettorPostBet_nonce' );

        $id=0;
        
        $existingsBets=  getBets($post->ID);
        
        foreach($existingsBets as $bet){
            showbetFormular($id, $bet->id,$bet->anstoss,$bet->liga,$bet->spiel,$bet->tip,$bet->einsatz,$bet->odd,$bet->buchmacher,$bet->sportart );
            echo "<hr>";
            $id++;
        }
        
        showbetFormular($id);
        
        echo '<br><button type="button" value="'.$id.'" class="button button-primary button-large" id="more_bet" name="publish">'.__("one more Bet","BettorPlugin").'</button>';     
}

function bettorPostBet_save( $post_id ) {
    $kickoff=  filter_input(INPUT_POST, "bettor_kickoff", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $league=  filter_input(INPUT_POST, "bettor_league", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $game=  filter_input(INPUT_POST, "bettor_game", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $tip=  filter_input(INPUT_POST, "bettor_tip", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $stake=  filter_input(INPUT_POST, "bettor_stake", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $odd=  filter_input(INPUT_POST, "bettor_odd", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $bookmaker=  filter_input(INPUT_POST, "bettor_bookmaker", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $sports=  filter_input(INPUT_POST, "bettor_sports", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $bet_id=filter_input(INPUT_POST, "bettor_id", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    
    $post = get_post($post_id);
    updatePostStatusBet($post_id, $post->post_status);
    if(count($kickoff)>0 && $post->post_type=='post'){
        $erg=saveBets($kickoff, $league, $game, $tip, $stake, $odd, $bookmaker, $sports, $post_id, $bet_id, $post->post_status);
        if($erg){        
//            write_log("Save Bets gut!");
        }else{
//            write_log("Save Bets schlecht! " +$erg);
        }
    }
}

function select_bookmaker($id, $bookmaker_tip){
    include_once("betfunctions.php");
    $bookmakers= getBookmakers();
   echo "<select id='bettor_bookmaker_".$id."' name='bettor_bookmaker[".$id."]'>";
    foreach($bookmakers as $bookmaker){
        if($bookmaker_tip==$bookmaker->id){
            echo "<option selected='selected' value='".$bookmaker->id."'>".$bookmaker->bookmaker."</option>"; 
        }else{
            echo "<option value='".$bookmaker->id."'>".$bookmaker->bookmaker."</option>"; 
        } 
    }
    echo "</select>";
}

function select_sports($id, $sports_tip){
    include_once("betfunctions.php");
    $sports=  getSports();
    echo "<select id='bettor_sports_".$id."' name='bettor_sports[".$id."]' class='bettor_1bottom'>";
    foreach($sports as $sport){
        if($sports_tip==$sport->id){
            echo "<option selected='selected' value='".$sport->id."'>".$sport->sports."</option>"; 
        }else{
            echo "<option value='".$sport->id."'>".$sport->sports."</option>"; 
        } 
    }
    echo "</select>";
}
 
function saveBets($kickoff, $league, $game, $tip, $stake, $odd, $bookmaker, $sports, $post_id, $bet_id, $post_status){
    for($i=0;$i<count($kickoff);$i++){
        if(is_null($league[$i]) || $league[$i]==="" ){
//            write_log("False 1");
            return false;
        }
        
        if(is_null($game[$i]) || $game[$i]===""){
//            write_log("False 2");
            return false;
        }        
        
        if(is_null($tip[$i]) || $tip[$i]===""){
//            write_log("False 3");
            return false;
        }
        
        if(is_null($stake[$i]) || $stake[$i]===""){
//            write_log("False 4");
            return false;
        }
        
        if(is_null($odd[$i]) || $odd[$i]===""){
//            write_log("False 5");
            return false;
        }
        
        if(is_null($bookmaker[$i]) || $bookmaker[$i]===""){
//            write_log("False 6");
            return false;
        }
        
        if(is_null($sports[$i]) || $sports[$i]===""){
//            write_log("False 7");
            return false;
        }
        
        global $wpdb;
        if(is_null($bet_id[$i]) || $bet_id[$i]===''){
            $erg=$wpdb->replace($wpdb->prefix . "bettor_bet",array( 
                    'anstoss' => $kickoff[$i], 
                    'liga'=>$league[$i], 
                    'sportart'=>$sports[$i], 
                    'spiel'=>$game[$i], 
                    'tip'=>$tip[$i], 
                    'einsatz'=>$stake[$i], 
                    'odd'=>$odd[$i], 
                    'buchmacher'=>$bookmaker[$i], 
                    'eingestellt' => date('Y-m-d H:i:s'), 
                    'kostenlos'=>true, 
                    'gruppe'=>"", 
                    'id_beitrag'=>$post_id,
                    'post_status'=>$post_status)
                    );
        }else{
            $erg=$wpdb->replace($wpdb->prefix . "bettor_bet",array( 
                    'anstoss' => $kickoff[$i], 
                    'liga'=>$league[$i], 
                    'sportart'=>$sports[$i], 
                    'spiel'=>$game[$i], 
                    'tip'=>$tip[$i], 
                    'einsatz'=>$stake[$i], 
                    'odd'=>$odd[$i], 
                    'buchmacher'=>$bookmaker[$i], 
                    'eingestellt' => date('Y-m-d H:i:s'), 
                    'kostenlos'=>true, 
                    'gruppe'=>"", 
                    'id_beitrag'=>$post_id,
                    'id'=>$bet_id[$i],
                    'post_status'=>$post_status)
                    );
        }
    }
    return true;
}

function showBetFormular($id, $betid='', $kickoff='', $league='', $game='', $tip='', $stake='', $odd='', $bookmaker='', $sports=''){
    echo "<span id='bettor_".$id."'>";
    echo '<input type="hidden" name="bettor_id['.$id.']" value="'.$betid.'">';
    echo '<label for="bettor_kickoff_'.$id.'" class="bettor_block">';
    _e("Kickoff","BettorPlugin");
    echo ':</label>';
    echo '<input type="text" id="bettor_kickoff_'.$id.'" class="bettor_kickoff" name="bettor_kickoff['.$id.']" value="' . $kickoff . '" size="25" />';

    echo '<label for="bettor_league_'.$id.'" class="bettor_block">';
    _e("League","BettorPlugin");
    echo ':</label>';
    echo '<input type="text" id="bettor_league_'.$id.'" name="bettor_league['.$id.']" value="' . $league . '" size="25" />';

    echo '<label for="bettor_game_'.$id.'" class="bettor_block">';
    _e("Game","BettorPlugin");
    echo ':</label>';
    echo '<input type="text" id="bettor_game_'.$id.'" name="bettor_game['.$id.']" value="' . $game . '" size="25" />';

    echo '<label for="bettor_tip_'.$id.'" class="bettor_block">';
    _e("Tip","BettorPlugin");
    echo ':</label>';
    echo '<input type="text" id="bettor_tip_'.$id.'" name="bettor_tip['.$id.']" value="' . $tip . '" size="25" />';


    echo '<label for="bettor_stake_'.$id.'" class="bettor_block">';
    _e("Stake","BettorPlugin");
    echo ':</label>';
    echo '<input type="text" id="bettor_stake_'.$id.'" name="bettor_stake['.$id.']" value="' . $stake . '" size="25" />';

    echo '<label for="bettor_odd_1" class="bettor_block">';
    _e("Odd","BettorPlugin");
    echo ':</label>';
    echo '<input type="text" id="bettor_odd_'.$id.'" name="bettor_odd['.$id.']" value="' . $odd . '" size="25" />';

    echo '<label for="bettor_bookmaker_'.$id.'" class="bettor_block">';
    _e("Bookmaker","BettorPlugin");
    echo ':</label>';
    select_bookmaker($id, $bookmaker);

    echo '<label for="bettor_sports_'.$id.'" class="bettor_block">';
    _e("Sports","BettorPlugin");
    echo ':</label>';
    select_sports($id, $sports);
    echo "</span>";
}

function updatePostStatusBet($post_id, $post_status){
    global $wpdb;
    $wpdb->update(
            $wpdb->prefix . "bettor_bet",
            array("post_status"=>$post_status),
            array( 'id_beitrag' => $post_id ), 
            array('%s'),
            array('%d')
            );
}
   
   