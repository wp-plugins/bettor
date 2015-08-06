<?php
/**
 * Example Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 * @since 0.1
 */
class bettor_auswertung extends WP_Widget {

    public function __construct() {
            parent::__construct(
                'bettor_widget',
                'Bettor Widget',
            array(
                'description' => __("Display the statistic of your bets as widget.","BettorPlugin")
            )
        );
    }
        
    public function form($instance){
            $defaults = array(
			'title' => '',
                        'link' => '',
                        'count_month' => 2
	    );
	    $instance = wp_parse_args((array)$instance, $defaults);

	    $title = $instance['title'];
            $count_month = $instance['count_month'];
            $link=$instance['link'];
	    ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __("Title","BettorPlugin").':'; ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
                        <label for="<?php echo $this->get_field_id('link'); ?>"><?php echo __("Statistic Link","BettorPlugin").':'; ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('link'); ?>" name="<?php echo $this->get_field_name('link'); ?>" type="text" value="<?php echo esc_attr($link); ?>" />
                        <label for="<?php echo $this->get_field_id('count_month'); ?>"><?php echo __("How many month back?","BettorPlugin").':'; ?></label> 
                        <select class="widefat" id="<?php echo $this->get_field_id('count_month'); ?>" name="<?php echo $this->get_field_name('count_month'); ?>"><?php
                            for($i=0;$i<=120;$i++){
                                if($i==$count_month){
                                    echo "<option selected>".$i."</option>";
                                }else{
                                    echo "<option>".$i."</option>";
                                }
                            }
                        ?>
                        </select>
                </p>
		<?php
	}    

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        
        $count_month=2;
        $link="";
        
        if(isset($instance['count_month'])){
            $count_month=$instance['count_month'];
        }
        
        if(isset($instance['link'])){
            $link=$instance['link'];
        }
        
        $show=bettorgetWidgetShow(bettorBetsBilanz($count_month), $link, $count_month);
        echo $before_widget;
        if(!empty($title)){
            echo $before_title . $title . $after_title;
            echo $show;
        }
        echo $after_widget;
    }



    /**
     * Update the widget settings.
     */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;

        /* Strip tags for title and name to remove HTML (important for text inputs). */
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['count_month'] = strip_tags($new_instance['count_month']);
        $instance['link'] = strip_tags($new_instance['link']);

        return $instance;
    }
}

function bettorgetWidgetShow($bet, $link, $count_month){
    $show="";
    $betrag=20;
    $currency="€";
    
    if($link!=""){
        $show.="<ul><a href='/statistik/' 'title='Statistik'>".__("Get detail statistics","BettorPlugin")."</a></ul>";
    }

    $option=  get_option('bettor_options');
    if(isset($option['unit'])){
        $betrag=$option['unit'];
        $currency=$option['currency'];
    }
    
    for($i=0;$i<$count_month;$i++){
        $date_arr= date('Y-m',strtotime("-".($count_month-$i-1)." month"));
        $date=date('m.Y',strtotime("-".($count_month-$i-1)." month"));
        $betcount=0;
        $gewinn=0;
        $color="";     
        
        if(isset($bet[$date_arr])){
            $betcount=$bet[$date_arr]['count'];
            $gewinn=$bet[$date_arr]['units'];
        }
        
        if($gewinn>=0){
            $color="bettor_green";
        }else{
            $color="bettor_maroon";
        }
        
        $show.= "   <p class='bettor_widget'><u><strong>" . $date . "</strong></u></p>
                    <span class='bettor_widget'><strong>".__("Tipps","BettorPlugin").": " . $betcount . "</strong></span>
                    <span class='bettor_widget'>".__("Win in units","BettorPlugin").": <strong><span class='".$color."'> " . round($gewinn,2) . "</strong></span></span>
                    <span class='bettor_widget'>".__("Win in","BettorPlugin")." ".$currency.": <strong><span class='".$color."'>" . number_format(round(($gewinn * $betrag),2),2,',','.') . " ".$currency."</strong></span></span>";
        
    }
    
    $color="";
    if($bet['all']['units']>=0){
        $color="bettor_green";
    }else{
        $color="bettor_maroon";
    }
    
    $d_odd=0;
    $yield=0;
    if($bet['all']['odd']>0){
        $yield=round(($bet['all']['units'] / $bet['all']['einsatz'] * 100), 2);
        $d_odd=$bet['all']['odd']/$bet['all']['count'];
    }
    
    $show.="<p class='bettor_widget'><u><strong>".__("Statistics","BettorPlugin")."</strong></u></p>
            <span class='bettor_widget'>".__("Tipps","BettorPlugin").": <strong>" . $bet['all']['count'] . "</strong></span>
            <span class='bettor_widget'>".__("Win in units","BettorPlugin").": <strong><span class='".$color."'>" . round($bet['all']['units'], 2) . "</strong></span></span>
            <span class='bettor_widget'>".__("Win in","BettorPlugin")." ".$currency.": <strong><span class='".$color."'>" . number_format(round(($bet['all']['units'] * $betrag),2),2,',','.')  . " ".$currency."</strong></span></span>
            <span class='bettor_widget'>".__("Yield","BettorPlugin").": <strong><span class='".$color."'>" . $yield . "</strong></span></span>
            <span class='bettor_widget'>Ø ".__("Odd","BettorPlugin").": <strong>" . round($d_odd, 2) . "</strong></span>
            <span class='bettor_widget'>".__("Units","BettorPlugin").": <strong>" . round($bet['all']['einsatz'], 2) . "</strong></span>
            <span class='bettor_widget'> </p>";
    
    $show.="<p class='bettor_widget'>".__("High of one unit","BettorPlugin")." : ".$betrag." ".$currency."</p>";

    return $show;
}