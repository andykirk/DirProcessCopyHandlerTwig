<?php
namespace DirProcessCopyHandlerTwig;

require_once dirname(__DIR__) . '/vendor/autoload.php';
use \Michelf\Markdown;

/**
 * TwigHandler
 *
 * @package DirProcessCopy
 * @author Andy Kirk
 * @copyright Copyright (c) 2021
 * @version 0.1
 **/
class DirProcessCopyHandlerTwig extends \DirProcessCopy\PluginHandler\PluginAbstract
{
    protected $input_extension  = 'twig';
    protected $output_extension = 'html';

    /**
     * TwigHandler::handle()
     *
     * @param string $filepath
     * @return bool
     * @access public
     **/
    public function handle(string $filepath = '')
    {
        $c = $this->config;

        $filepath_relative = str_replace($c['dpc_input_dir'], '', $filepath);

        // The file really should exist or this wont have been called, but check anyway:
        if (!file_exists($filepath)) {
            return false;
        }

        if (isset($c['twig_handler']['templates_dir'])) {
            $templates_dir = $c['twig_handler']['templates_dir'];
        } else {
            trigger_error('No Twig template directory specified in config.', E_USER_ERROR);
            // We can't render anything so the handler is broken.
            return false;

            // We could return true here to just copy the file if that's a more useful default.
            // I'm not sure.
        }

        $loader = new \Twig\Loader\FilesystemLoader($templates_dir);
        $twig   = new \Twig\Environment($loader);
       
        // Add markdown filter:
        $md_filter = new \Twig\TwigFilter('md', function ($string) {
            $new_string = '';
            // Parse md here
            $new_string = Markdown::defaultTransform($string);
            return $new_string;
        });

        $twig->addFilter($md_filter);
        

        // Also these filters might be handy:
        /*
        // Add pad filter:
        $pad_filter = new \Twig\TwigFilter('pad', function ($string, $length, $pad = ' ', $type = 'right') {
            $new_string = '';
            switch ($type) {
                case 'right':
                    $type = STR_PAD_RIGHT;
                    break;
                case 'left':
                    $type = STR_PAD_LEFT;
                    break;
                case 'both':
                    break;
                    $type = STR_PAD_BOTH;
            }
            $length = (int) $length;
            $pad    = (string) $pad;
            $new_string = str_pad($string, $length, $pad, $type);

            return $new_string;
        });
        $twig->addFilter($pad_filter);

        // Add regex_replace filter:
        $regex_replace_filter = new \Twig\TwigFilter('regex_replace', function ($string, $search = '', $replace = '') {
            $new_string = '';

            $new_string = preg_replace($search, $replace, $string);

            return $new_string;
        });
        $twig->addFilter($regex_replace_filter);

        // Add html_id filter:
        $html_id_filter = new \Twig\TwigFilter('html_id', function ($string) {
            $new_string = '';

            $new_string = $this->htmlID($string);

            return $new_string;
        });
        $twig->addFilter($html_id_filter);

        // Add sum filter:
        $sum_filter = new \Twig\TwigFilter('sum', function ($array) {
            return array_sum($array);
        });
        $twig->addFilter($sum_filter);

        // Add str_replace filter:
        $pad_filter = new \Twig\TwigFilter('str_replace', function ($string, $search = '', $replace = '') {
            $new_string = '';

            $new_string = str_replace( $search, $replace, $string);

            return $new_string;
        });
        $twig->addFilter($pad_filter);
        */


        if (!empty($c['twig_handler']['data']) && is_array($c['twig_handler']['data'])) {
            $output = $twig->render($filepath_relative, ['data' => $c['twig_handler']['data']]);
        } else {
            $output = $twig->render($filepath_relative);
        }
        
        $tidy_available = extension_loaded('tidy');

        if ($tidy_available) {
            // Tidy the output:
            $config = [
                'indent'       => true,
                'output-xml'   => true,
                'input-xml'    => true,
                'wrap'         => '1000'
            ];

            $tidy = new \tidy();
            $tidy->parseString($output, $config, 'utf8');
            $tidy->cleanRepair();
            $output = tidy_get_output($tidy);

        }

        $tmp_filepath = trim(str_replace($c['dpc_input_dir'], $c['dpc_process_dir'], $filepath), '.twig');

        file_put_contents($tmp_filepath, $output);

        // We don't want the original file added to the 'copy list' so return false:
        return false;
    }

    /**
     * Creates an HTML-friendly string for use in id's
     *
     * @param string $text
     * @return string
     * @access public
     */
    /*public function htmlID($text)
    {
        if (!is_string($text)) {
            trigger_error('Function \'html_id\' expects argument 1 to be an string', E_USER_ERROR);
            return false;
        }
        $return = strtolower(trim(preg_replace('/\s+/', '-', self::stripPunctuation($text))));
        return $return;
    }*/


    /**
     * Strips punctuation from a string
     *
     * @param string $text
     * @return string
     * @access public
     */
    /*public function stripPunctuation($text)
    {
        if (!is_string($text)) {
            trigger_error('Function \'strip_punctuation\' expects argument 1 to be an string', E_USER_ERROR);
            return false;
        }
        $text = html_entity_decode($text, ENT_QUOTES);

        $urlbrackets = '\[\]\(\)';
        $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
        $urlspaceafter = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
        $urlall = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

        $specialquotes = '\'"\*<>';

        $fullstop = '\x{002E}\x{FE52}\x{FF0E}';
        $comma = '\x{002C}\x{FE50}\x{FF0C}';
        $arabsep = '\x{066B}\x{066C}';
        $numseparators = $fullstop . $comma . $arabsep;

        $numbersign = '\x{0023}\x{FE5F}\x{FF03}';
        $percent = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
        $prime = '\x{2032}\x{2033}\x{2034}\x{2057}';
        $nummodifiers = $numbersign . $percent . $prime;
        $return = preg_replace(
        array(
            // Remove separator, control, formatting, surrogate,
            // open/close quotes.
            '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
            // Remove other punctuation except special cases
            '/\p{Po}(?<![' . $specialquotes .
            $numseparators . $urlall . $nummodifiers . '])/u',
            // Remove non-URL open/close brackets, except URL brackets.
            '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
            // Remove special quotes, dashes, connectors, number
            // separators, and URL characters followed by a space
            '/[' . $specialquotes . $numseparators . $urlspaceafter .
            '\p{Pd}\p{Pc}]+((?= )|$)/u',
            // Remove special quotes, connectors, and URL characters
            // preceded by a space
            '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
            // Remove dashes preceded by a space, but not followed by a number
            '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
            // Remove consecutive spaces
            '/ +/',
            ), ' ', $text);
        $return = str_replace('/', '_', $return);
        return str_replace("'", '', $return);
    }*/
}