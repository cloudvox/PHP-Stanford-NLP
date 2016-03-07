<?php
/**
 * PHP interface for Stanford POS Tagger
 * http://nlp.stanford.edu/downloads/tagger.shtml
 *
 * Part-Of-Speech Tag List
 * http://www.ling.upenn.edu/courses/Fall_2003/ling001/penn_treebank_pos.html
 *
 * @link https://github.com/agentile/PHP-Stanford-NLP
 * @version 0.1.0
 * @author Anthony Gentile <asgentile@gmail.com>
 */
namespace StanfordNLP;

class Sentiment extends StanfordTagger {


    /**
     * Constructor!
     *
     * @param $jar string path stanford coreNLP jar file
     * @param $java_options mixed command line arguments to pass
     *
     * @return null
     */
    public function __construct($jar, $javaOptions = array('-mx300m'))
    {
        parent::__construct();
        $this->setJar($jar);
        $this->setJavaOptions($javaOptions);
    }

    /**
     * Model getter
     *
     * @return mixed
     */
    public function getSentiment($string)
    {
        // Reset errors and output
        $this->setErrors(null);
        $this->setOutput(null);

        // Make temp file to store sentences.
        $tmpfname = tempnam(DIRECTORY_SEPARATOR . 'tmp', 'phpnlptag');
        chmod($tmpfname, 0644);
        $handle = fopen($tmpfname, "w");

        $sentences = preg_split('/([!?.])/', $string, -1, PREG_SPLIT_NO_EMPTY);

        // foreach ($sentences as $k => $v) {
        //     $sentences[$k] = implode(' ', $v);
        // }
        $str = implode(".\n", $sentences);

        fwrite($handle, $str);
        fclose($handle);

        // Create process to run stanford ner.
        $descriptorspec = array(
           0 => array("pipe", "r"),  // stdin
           1 => array("pipe", "w"),  // stdout
           2 => array("pipe", "w")   // stderr
        );

        $options = implode(' ', $this->getJavaOptions());
        $osSeparator = $this->php_os == 'windows' ? ';' : ':';


        $cmd = escapeshellcmd(
            $this->getJavaPath()
            . " $options -cp \""
            . $this->getJar()
            . "{$osSeparator}\" edu.stanford.nlp.sentiment.SentimentPipeline "
            . " -file "
            . $tmpfname
        );



        $process = proc_open($cmd, $descriptorspec, $pipes, dirname($this->getJar()));
        //dump($process);

        $output = null;
        $errors = null;
        if (is_resource($process)) {
            // We aren't working with stdin
            fclose($pipes[0]);

            // Get output
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // Get any errors
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // close pipe before calling proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
            if ($return_value == -1) {
                throw new Exception("Java process returned with an error (proc_close).");
            }
        }

        //unlink($tmpfname);

        if ($errors) {
            $this->setErrors($errors);
        }

        if ($output) {
            dump($output);
            $this->setOutput($output);
        }

        return $this->parseOutput();
        //return $output;
    }
}
