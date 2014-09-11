<?php

$simpulThemeOptions = new SimpulThemeOptions(
    array(
        'namespace' => 'toddcochran',
        'sliders' => false,
        'sections' => array(
            'front_page' => array(
                'first_box' => 'imagelink',
                'second_box' => 'imagelink',
                'third_box' => 'imagelink',
                'fourth_box' => 'textarealink',
                'fifth_box' => 'imagelink',
                'sixth_box' => 'textarealink'
            ),
            'about_sidebar' => array(
                'first_box' => 'imagelinkquote',
                'second_box' => 'imagelinkquote',
                'third_box' => 'imagelinkquote',
                'fourth_box' => 'imagelinkquote',
                'fifth_box' => 'imagelinkquote'
            ),
            'written_sidebar' => array(
                'first_box' => 'imagelinkquote',
                'second_box' => 'imagelinkquote',
                'third_box' => 'imagelinkquote',
                'fourth_box' => 'imagelinkquote',
                'fifth_box' => 'imagelinkquote'
            ),
            'works_sidebar' => array(
                'first_box' => 'imagelinkquote',
                'second_box' => 'imagelinkquote',
                'third_box' => 'imagelinkquote',
                'fourth_box' => 'imagelinkquote',
                'fifth_box' => 'imagelinkquote'
            ),
            'press_sidebar' => array(
                'first_box' => 'imagelinkquote',
                'second_box' => 'imagelinkquote',
                'third_box' => 'imagelinkquote',
                'fourth_box' => 'imagelinkquote',
                'fifth_box' => 'imagelinkquote'
            ),
            'events_sidebar' => array(
                'first_box' => 'imagelinkquote',
                'second_box' => 'imagelinkquote',
                'third_box' => 'imagelinkquote',
                'fourth_box' => 'imagelinkquote',
                'fifth_box' => 'imagelinkquote'
            )
        )
    )
);

function get_simpul_theme_options($section = null){
    global $simpulThemeOptions;
    return $simpulThemeOptions->getThemeOptions($section);
}
class SimpulThemeOptions {
    function __construct($args){
        foreach($args as $key => $arg):
            $this->{$key} = $arg;
        endforeach;
        add_action( 'admin_init', array($this, 'init') );
        add_action( 'admin_menu', array($this, 'addPage') );
    }
    /**
     * Init plugin options to white list our options
     */
    function init(){
        register_setting( $this->namespace . '_options', $this->namespace . '_theme_options', array($this, 'validate') );
        add_action( 'admin_head', array($this, 'upload'), 11);
        wp_enqueue_style('thickbox');
        
        if(!wp_script_is('media-upload')):
            wp_enqueue_script('media-upload');
        endif;
        if(!wp_script_is('thickbox')):
            wp_enqueue_script('thickbox');
        endif;
    }
    /**
     * Load up the menu page
     */
    function addPage(){
         add_theme_page( __( 'Theme Options', $this->namespace . '_theme' ), __( 'Theme Options', $this->namespace . '_theme' ), 'edit_theme_options', 'theme_options', array($this, 'doPage') );
    }
    /**
     * Create the options page
     */
    function doPage(){
        global $select_options, $radio_options;
        
        if ( !isset( $_REQUEST['settings-updated'] ) ):
            $_REQUEST['settings-updated'] = false;
        endif;
        $this->options = self::getThemeOptions();
        ?>
        <div class="wrap">
            <?php screen_icon(); echo "<h2>" . get_current_theme() . __( ' Theme Options', $this->namespace . '_theme' ) . "</h2>"; ?>
            <h2 class="nav-tab-wrapper">
            <?php $i = 0; ?>
            <?php foreach($this->sections as $section => $fields): ?>
                <a href="#<?php echo $section; ?>" class="nav-tab<?php if($i === 0) echo ' nav-tab-active'; ?>"><?php echo self::getLabel($section); ?></a>
                <?php $i++; ?>
            <?php endforeach; ?>
            </h2>
            <?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
            <div class="updated fade"><p><strong><?php _e( 'Options saved', $this->namespace . '_theme' ); ?></strong></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php 
                settings_fields( $this->namespace . '_options' );
                ?>
                <?php if(!empty($this->sections)) self::sections($this->sections); ?>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e( 'Save Options', $this->namespace . '_theme' ); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
    function getLabel($key){
        $glued = array();
        if( strpos( $key, "-" ) ) $pieces = explode( "-", $key );
        elseif( strpos( $key, "_" ) ) $pieces = explode( "_", $key );
        else $pieces = explode(" ", $key);
        
        foreach($pieces as $piece):
            if($piece == "id"):
                $glued[] = strtoupper($piece);
            else:
                $glued[] = ucfirst($piece);
            endif;
        endforeach;
            
        return implode(" ", $glued);
    }
    function validate($input){
        global $select_options, $radio_options;

        // Our checkbox value is either 0 or 1
        if ( ! isset( $input['option1'] ) )
            $input['option1'] = null;
        $input['option1'] = ( $input['option1'] == 1 ? 1 : 0 );

        // Say our text option must be safe text with no HTML tags
        $input['sometext'] = wp_filter_nohtml_kses( $input['sometext'] );

        // Our select option must actually be in our array of select options
        if ( ! array_key_exists( $input['selectinput'], $select_options ) )
            $input['selectinput'] = null;

        // Our radio option must actually be in our array of radio options
        if ( ! isset( $input['radioinput'] ) )
            $input['radioinput'] = null;
        if ( ! array_key_exists( $input['radioinput'], $radio_options ) )
            $input['radioinput'] = null;

        // Say our textarea option must be safe text with the allowed tags for posts
        $input['sometextarea'] = wp_filter_post_kses( $input['sometextarea'] );

        return $input;
    }
    function upload(){
        if(empty($GLOBALS['simpul_meta_upload']) && $_GET['page'] == 'theme_options'):
            $GLOBALS['simpul_meta_upload'] = true;
            $script = '
            <script type="text/javascript">
                jQuery(document).on(\'click\', \'.nav-tab\', function(e){
                    e.preventDefault();
                    jQuery(\'.nav-tab\').removeClass(\'nav-tab-active\');
                    jQuery(this).addClass(\'nav-tab-active\');
                    jQuery(\'.sections\').hide();
                    jQuery(jQuery(this).attr(\'href\')).show();
                });
                var original_send_to_editor = "";  
                var modified_send_to_editor = "";
                var formfield = "";
                var hrefurl = "";
                 
                jQuery(document).ready( function() {    
                    
                    original_send_to_editor = window.send_to_editor;
                    
                    modified_send_to_editor = function(html) {
                                hrefurl = jQuery("img",html).attr("src");
                                console.log(jQuery(html));
                                if(!hrefurl) {
                                    hrefurl = jQuery(html).attr("href"); // We do this to get Links like PDFs
                                }
                                hrefurl = hrefurl.substr(hrefurl.indexOf("/",8)); // Skips "https://" and extracts after first instance of "/" for relative URL, ex. "/wp-content/themes/currentheme/images/etc.jpg"
                                console.log(hrefurl);
                                jQuery("#" + formfield).val(hrefurl);
                                tb_remove();
                                window.send_to_editor = original_send_to_editor;
                            };          
                    
                    jQuery(".simpul_meta_upload").click(function() {
                        window.send_to_editor = modified_send_to_editor;
                        formfield = jQuery(this).attr("data-input");
                        tb_show("Add File", "media-upload.php?TB_iframe=true");
                        console.log(formfield);
                        return false;
                    });
                });
            </script>';
            echo $script;
        endif;
    }
    function sections($sections){
        $i = 0;
        foreach($sections as $section => $fields):
            echo '<div class="sections" id="' . $section .  '"' . (($i !== 0) ? ' style="display: none"' : '') . '>';
            echo '<h2>' . self::getLabel($section) . '</h2>';
            echo '<table class="form-table">';
            foreach($fields as $field => $format):
                self::format($section, $field, $format);
            endforeach;
            echo '</table>';
            echo '</div>';
            $i++;
        endforeach;

    }
    function format($section, $field, $format){
       $fieldVars = self::setFieldVars($section, $field, $format);
        if(method_exists($this, $fieldVars->format)):
            ?>
            <tr valign="top">
                <th scope="row"><?php _e( $this->getLabel($fieldVars->field) . ": ", $this->namespace . '_theme' ); ?></th>
                <td style="vertical-align: top;"><?php $this->$format($fieldVars); ?></td>
            </tr>
            <?php
        endif;
        return;
    }
    function setFieldVars($section, $field, $format){
        $fieldVars = new stdClass;
        $fieldVars->field = strtolower($field);
        $fieldVars->section = strtolower($section);
        $fieldVars->format = strtolower($format);
        $fieldVars->name = strtolower($this->namespace . '_theme_options[' . $fieldVars->section . '][' . $fieldVars->field . ']');
        
        $fieldVars->value = !empty($this->options[$fieldVars->section][$fieldVars->field]) ? $this->options[$fieldVars->section][$fieldVars->field] : '';
        $fieldVars->id = $this->namespace . '_theme_options_' . $fieldVars->section . '_' . $fieldVars->field;
        $fieldVars->class = $this->namespace . '-theme_options-' .$fieldVars-> $section . '-' . $fieldVars->field;

        return $fieldVars;
    }
    function setArrayFieldVars($section, $field, $format, $i){
        $fieldVars = new stdClass;
        $fieldVars->field = strtolower($field);
        $fieldVars->section = strtolower($section);
        $fieldVars->format = strtolower($format);
        $fieldVars->name = strtolower($this->namespace . '_theme_options[' . $fieldVars->section . '][' . $fieldVars->field . '][' . $fieldVars->field . '][' . $i . ']');
        
        $fieldVars->value = !empty($this->options[$fieldVars->section][$fieldVars->field][$fieldVars->field][$i]) ? $this->options[$fieldVars->section][$fieldVars->field][$fieldVars->field][$i] : '';
        $fieldVars->id = $this->namespace . '_theme_options_' . $fieldVars->section . '_' . $fieldVars->field . '_' . $fieldVars->field . '_' . $i;
        $fieldVars->class = $this->namespace . '-theme_options-' .$fieldVars-> $section . '-' . $fieldVars->field . '-' . $fieldVars->field . '-' . $i;

        return $fieldVars;
    }
    function text($field, $name = 'text', $placeholder = 'Text', $description = ''){
        ?>
        <input placeholder="<?php echo $placeholder; ?>" id="<?php echo $field->id; ?>_<?php echo $name; ?>" class="regular-text ltr" type="text" name="<?php echo $field->name; ?>[<?php echo $name; ?>]" value="<?php esc_attr_e( $field->value[$name] ); ?>" />
        <?php
        if($description): ?><p class="description"><?php echo $description; ?></p><?php endif;
    }
    function src($field, $name = 'src', $placeholder = '', $description = ''){
        $this->text($field, $name, $placeholder); ?>
        <button data-input="<?php echo $field->id; ?>_<?php echo $name; ?>" class="simpul_meta_upload button-secondary" type="button">Browse</button>
        <?php if($description) ?><p class="description"><?php echo $description; ?></p><?php
    }
    
    function textarea($field, $name = 'text', $placeholder = '', $description = ''){
        ?><div style="margin-bottom: 20px;"><?php
        wp_editor($field->value[$name], $field->id, array('textarea_name' => $field->name . '[' . $name . ']', 'textarea_rows' => 4) );
        ?></div>
        <?php if($description) ?><p class="description"><?php echo $description; ?></p><?php
    }
    function image($field, $name = 'src'){
        $this->src($field, $name, 'Image Link', 'Press Browse to find and image or just Insert a Link to an Image');
    }
    function label($field){
        $this->text($field, 'label', 'Label', 'Label');
    }
    function link($field){
        $this->text($field, 'link', 'Link', 'The Link (example: http://mylink.com/mypage');
        echo '<br>';
        $this->text($field, 'label', 'Label', 'The Link\'s Label (example: <a href="' .  (!empty($field->value['link']) ? $field->value['link'] : '#') . '" target="_blank">' .  (!empty($field->value['label']) ? $field->value['label'] : 'Click Here!') . '</a>)');    }
   
    function imagelink($field){
        $this->image($field);
        echo '<br>';
        $this->link($field);
    }
    function textarealink($field){
        $this->textarea($field);
        $this->link($field);
    }
    function imagelinkquote($field){
        $this->imagelink($field);
        $this->text($field, 'quote', 'Quote', 'The Source being Quoted (example:  Martin Luther King)');
    }
    function imagelinktextarea($field){
        $this->imagelink($field);
        $this->textarea($field, 'text', 'Content', 'HMTL Content that usually sits on top of the Image.');
    }
    function slider($field){
        $this->text($field, 'effect', 'Effect', 'Slide Effect (example: fade,slide)');
        $this->text($field, 'timing', 'Timing', 'Timing in Seconds (example: 5 would be 5 seconds)');
        $this->text($field, 'total', 'Total', 'Number of Sliders');
        if(!empty($field->value['total']) && $field->value['total'] > 0):
            for($i = 1; $i <= $field->value['total']; $i++):
                $sliderField = self::setArrayFieldVars($field->section, $field->format, $field->format, $i);
                ?><h3>Slider <?php echo $i; ?></h3><?php
                $this->imagelinktextarea($sliderField);
            endfor;
        endif;
    }

    function getThemeOptions($section = null){
        if(!empty($section)):
            $this->options = get_option( $this->namespace . '_theme_options' );
            return $this->options[$section];
        endif;
        return get_option( $this->namespace . '_theme_options' );
    }
}