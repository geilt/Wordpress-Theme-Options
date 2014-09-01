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
        ?>
        <div class="wrap">
            <?php screen_icon(); echo "<h2>" . get_current_theme() . __( ' Theme Options', $this->namespace . '_theme' ) . "</h2>"; ?>
            <?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
            <div class="updated fade"><p><strong><?php _e( 'Options saved', $this->namespace . '_theme' ); ?></strong></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php 
                settings_fields( $this->namespace . '_options' );
                $options = self::getThemeOptions();
                ?>
                <?php if(!empty($this->sliders)) self::slider($options); ?>
                <?php if(!empty($this->sections)) self::sections($this->sections, $options); ?>
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
    function slider($options){
        $fields = array(
            'image' => 'src',
            'image_link' => 'text', 
            'title' => 'text'
        );
        ?>

        <table class="form-table">
        <th colspan="2"><h2>Home Slider Options</h2></th>
        <tr valign="top"><th scope="row"><?php _e( 'Number of Sliders', $this->namespace . '_theme' ); ?></th>
            <td>
                <input id="<?php echo $this->namespace; ?>_theme_options[slider_number]" class="regular-text" type="text" name="<?php echo $this->namespace; ?>_theme_options[slider_number]" value="<?php esc_attr_e( $options['slider_number'] ); ?>" />
                <label class="description" for="<?php echo $this->namespace; ?>_theme_options[slider_number]"><?php _e( '', $this->namespace . '_theme' ); ?></label>
            </td>
        </tr>
        <tr>
            <td>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e( 'Save Options', $this->namespace . '_theme' ); ?>" />
                </p>
            </td>
        </tr>
            
        <?php
        
        $j = 0;
        
        for($i = 1; $i <= $options['slider_number']; $i++) :
        
            ?><th><h3>Slider <?php echo $i; ?></h3></th><?php
        
            foreach($fields as $field => $type) :
                switch($type) :
                    case 'text':
                        ?>
                        <tr valign="top"><th scope="row"><?php _e( 'Slider ' . $i . " " . $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                            <td>
                                <input id="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]" class="regular-text" type="text" name="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]" value="<?php esc_attr_e( $options[$i][$field] ); ?>" />
                                <label class="description" for="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]"><?php _e( '', $this->namespace . '_theme' ); ?></label>
                            </td>
                        </tr>
                    <?php
                        break;
                    case 'src':
                        $j++;
                        ?>
                        <tr valign="top"><th scope="row"><?php _e( 'Slider ' . $i . " " . $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                            <td>
                                <input id="<?php echo $this->namespace; ?>_theme_options_<?php echo $i ?>_<?php echo $field ?>" class="regular-text <?php echo $this->namespace; ?>_theme_options_<?php echo $i ?>_<?php echo $field ?>" type="text" name="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]" value="<?php esc_attr_e( $options[$i][$field] ); ?>" />
                                <label class="description" for="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]"><?php _e( '', $this->namespace . '_theme' ); ?></label>
                                <button data-input="<?php echo $this->namespace; ?>_theme_options_<?php echo $i ?>_<?php echo $field ?>" class="simpul_meta_upload button-secondary" name="<?php echo $this->namespace; ?>_theme_options_src_<?php echo $i . '_' . $j ?>" type="button">Browse</button>
                            </td>
                        </tr>
                    <?php
                        break;
                    case 'textarea':
                        ?>
                        <tr valign="top"><th scope="row"><?php _e( 'Slider ' . $i . " " . $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                            <td>
                                <textarea id="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]" class="large-text" cols="50" rows="10" name="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]"><?php echo esc_textarea( $options[$i][$field] ); ?></textarea>
                                <label class="description" for="<?php echo $this->namespace; ?>_theme_options[<?php echo $i ?>][<?php echo $field ?>]"><?php _e( '', $this->namespace . '_theme' ); ?></label>
                            </td>
                        </tr>
                    <?php
                        break;
                endswitch;
            endforeach;
        endfor; ?>
        </table>
        <?php
    }
    function sections($sections, $options){
        foreach($sections as $section => $fields):
            echo '<h2>' . self::getLabel($section) . '</h2>';
            echo '<table class="form-table">';
            foreach($fields as $field => $format):
                self::format($options, $section, $field, $format);
            endforeach;
            echo '</table>';
        endforeach;

    }
    function format($options, $section, $field, $format){
        $fieldName = $this->namespace . '_theme_options[' . $section . '][' . $field . ']';
        $fieldValue = !empty($options[$section][$field]) ? $options[$section][$field] : '';
        $className = $this->namespace . '_theme_options_' . $section . '_' . $field;
        switch($format) :
            case 'text':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <input id="<?php echo $fieldName; ?>" class="regular-text" type="text" name="<?php echo $fieldName; ?>" value="<?php esc_attr_e( $fieldValue ); ?>" />
                    </td>
                </tr>
            <?php
                break;
            case 'link':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <input placeholder="Link" id="<?php echo $fieldName; ?>[link]" class="regular-text" type="text" name="<?php echo $fieldName; ?>[link]" value="<?php esc_attr_e( $fieldValue['link'] ); ?>" />
                        <input placeholder="Label" id="<?php echo $fieldName; ?>[label]" class="regular-text" type="text" name="<?php echo $fieldName; ?>[label]" value="<?php esc_attr_e( $fieldValue['label'] ); ?>" /> Label
                    </td>
                </tr>
            <?php
                break;
            case 'src':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <input id="<?php echo $className; ?>" class="regular-text <?php echo $className; ?>" type="text" name="<?php echo $fieldName; ?>" value="<?php esc_attr_e( $fieldValue ); ?>" />
                        <button data-input="<?php echo $className; ?>" class="simpul_meta_upload button-secondary" type="button">Browse</button>
                    </td>
                </tr>
            <?php
                break;
            case 'imagelink':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <input placeholder="Image" id="<?php echo $className; ?>" class="regular-text <?php echo $className; ?>" type="text" name="<?php echo $fieldName; ?>[image]" value="<?php esc_attr_e( $fieldValue['image'] ); ?>" />
                        <button data-input="<?php echo $className; ?>" class="simpul_meta_upload button-secondary" type="button">Browse</button><br>
                        <input placeholder="Link" id="<?php echo $fieldName; ?>[link]" class="regular-text" type="text" name="<?php echo $fieldName; ?>[link]" value="<?php esc_attr_e( $fieldValue['link'] ); ?>" /> Link
                    </td>
                </tr>
            <?php
                break;
            case 'imagelinkquote':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <input placeholder="Image" id="<?php echo $className; ?>" class="regular-text <?php echo $className; ?>" type="text" name="<?php echo $fieldName; ?>[image]" value="<?php esc_attr_e( $fieldValue['image'] ); ?>" />
                        <button data-input="<?php echo $className; ?>" class="simpul_meta_upload button-secondary" type="button">Browse</button><br>
                        <input placeholder="Link" id="<?php echo $fieldName; ?>[link]" class="regular-text" type="text" name="<?php echo $fieldName; ?>[link]" value="<?php esc_attr_e( $fieldValue['link'] ); ?>" /> Link<br>
                        <input placeholder="Quote" id="<?php echo $fieldName; ?>[quote]" class="regular-text" type="text" name="<?php echo $fieldName; ?>[quote]" value="<?php esc_attr_e( $fieldValue['quote'] ); ?>" /> Quote
                    </td>
                </tr>
            <?php
                break;
            case 'textarea':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <?php wp_editor( esc_textarea( $fieldValue ), $className, array('textarea_name' => $fieldName, 'textarea_rows' => 4) ); ?> 
                    </td>
                </tr>
            <?php
                break;
            case 'textarealink':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <?php wp_editor( $fieldValue['text'], $className, array('textarea_name' => $fieldName . '[text]', 'textarea_rows' => 4, ) ); ?> 
                        <input placeholder="Link" style="margin-top: 10px;" id="<?php echo $fieldName; ?>[link]" class="regular-text" type="text" name="<php echo $fieldName; ?>[link]" value="<?php esc_attr_e( $fieldValue['link'] ); ?>" /> Link
                    </td>
                </tr>
            <?php
                break;
            case 'link':
                ?>
                <tr valign="top"><th scope="row"><?php _e( $this->getLabel($field) . ": ", $this->namespace . '_theme' ); ?></th>
                    <td>
                        <input id="<?php echo $fieldName; ?>[link]" class="regular-text" type="text" name="<?php echo $fieldName; ?>[link]" value="<?php esc_attr_e( $fieldValue['link'] ); ?>" />
                        <input id="<?php echo $fieldName; ?>[text]" class="regular-text" type="text" name="<?php echo $fieldName; ?>[text]" value="<?php esc_attr_e( $fieldValue['text'] ); ?>" />
                    </td>
                </tr>
            <?php
                break;
        endswitch;
    }
    function getThemeOptions($section = null){
        if(!empty($section)):
            $options = get_option( $this->namespace . '_theme_options' );
            return $options[$section];
        endif;
        return get_option( $this->namespace . '_theme_options' );
    }
}
