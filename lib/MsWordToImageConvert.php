<?php

define('MS_WORD_TO_IMAGE_CONVERT_LIBDIR', dirname(__FILE__) . '/MsWordToImageConvert/');
require_once(MS_WORD_TO_IMAGE_CONVERT_LIBDIR . 'Input.php');
require_once(MS_WORD_TO_IMAGE_CONVERT_LIBDIR . 'InputType.php');
require_once(MS_WORD_TO_IMAGE_CONVERT_LIBDIR . 'Output.php');
require_once(MS_WORD_TO_IMAGE_CONVERT_LIBDIR . 'OutputType.php');
require_once(MS_WORD_TO_IMAGE_CONVERT_LIBDIR . 'Exception.php');

class MsWordToImageConvert
{
    /**
     * @var string
     */
    private $apiUser;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var MsWordToImageConvert\Input
     */
    private $input;

    /**
     * @var MsWordToImageConvert\Output
     */
    private $output;

    /**
     * Constructs a new conversion task for given account
     * @param string $apiUser
     * @param string $apiKey
     */
    public function __construct($apiUser, $apiKey)
    {
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
        $this->input = null;
        $this->output = null;
    }

    /**
     * Sets the input of conversion to given URL
     * @param string $filename
     */
    public function fromFile($filename)
    {
        $this->input = new MsWordToImageConvert\Input(MsWordToImageConvert\InputType::File, $filename);
    }

    /**
     * Sets the input of the conversion to given URL
     * @param string $url
     */
    public function fromURL($url)
    {
        $this->input = new MsWordToImageConvert\Input(MsWordToImageConvert\InputType::URL, $url);
    }

    /**
     * Converts the input word document to image
     * And saves it in the given file name
     * @param string $filename
     * @return bool
     */
    public function toFile($filename)
    {
        $this->output = new MsWordToImageConvert\Output(MsWordToImageConvert\OutputType::File, $filename);
        return $this->convert();
    }

    /**
     * Converts the input word document to image
     * And returns it as Bas64 encoded string
     * @return bool|string
     */
    public function toBase46EncodedString()
    {
        $this->output = new MsWordToImageConvert\Output(MsWordToImageConvert\OutputType::Base64EncodedString);
        return $this->convert();
    }

    /**
     * Does the actual conversion
     * @return mixed
     * @throws \MsWordToImageConvert\Exception
     */
    private function convert()
    {
        if ($this->input === null) {
            throw new \MsWordToImageConvert\Exception("Input was not set. Try calling \$msWordToImageConvert->fromURL first");
        }

        if ($this->output === null) {
            throw new \MsWordToImageConvert\Exception("Output was not set.");
        }

        if (!function_exists("curl_init")) {
            throw new \MsWordToImageConvert\Exception("cURL library is required for MsWordToImageConvert");
        }

        $inputType = $this->input->getType();
        $outputType = $this->output->getType();

        if ($inputType === MsWordToImageConvert\InputType::URL && $outputType === MsWordToImageConvert\OutputType::File) {
            return $this->convertFromURLToFile();
        } else if ($inputType === MsWordToImageConvert\InputType::URL && $outputType === MsWordToImageConvert\OutputType::Base64EncodedString) {
            return $this->convertFromURLToBase64EncodedString();
        } else if ($inputType === MsWordToImageConvert\InputType::File && $outputType === MsWordToImageConvert\OutputType::File) {
            return $this->convertFromFileToFile();
        } else if ($inputType === MsWordToImageConvert\InputType::File && $outputType === MsWordToImageConvert\OutputType::Base64EncodedString) {
            return $this->convertFromFileToBase64EncodedString();
        } else {
            throw new \MsWordToImageConvert\Exception("Invalid Input/Output combination. Cannot convert from InputType($inputType) to OutputType($outputType)");
        }
    }

    /**
     * Tries to open output file
     * This function only makes sense if conversion output is set to file
     * @return resource
     * @throws \MsWordToImageConvert\Exception
     */
    private function tryOpenOutputFile()
    {
        $out = fopen($this->output->getValue(), "wb");
        if (!$out) {
            throw new \MsWordToImageConvert\Exception("Couldn't fopen output file: " . $this->output->getValue());
        }

        return $out;
    }

    /**
     * Converts a given word file to image file
     * @return bool
     * @throws \MsWordToImageConvert\Exception
     */
    private function convertFromFileToFile()
    {
        $outputRealPath = realpath($this->input->getValue());
        if (!$outputRealPath) {
            throw new \MsWordToImageConvert\Exception("realpath() returned false for input file '" . $this->input->getValue() . "'");
        }

        $out = $this->tryOpenOutputFile();
        return $this->executeCurlPost([
            'file_contents' => '@' . $outputRealPath
        ], [
            CURLOPT_FILE => $out
        ]);
    }

    /**
     * Converts from file to Base64 string
     * @return string
     * @throws \MsWordToImageConvert\Exception
     */
    private function convertFromFileToBase64EncodedString()
    {
        throw new \MsWordToImageConvert\Exception("Not implemented yet!");
    }

    /**
     * Converts from URL to Base64 string
     * @return string
     */
    private function convertFromURLToBase64EncodedString()
    {
        $curlResult = $this->executeCurlPost([
            'url' => urlencode($this->input->getValue())
        ], [
            CURLOPT_RETURNTRANSFER => 1
        ]);
        $curlResult = base64_encode($curlResult);

        return $curlResult;
    }

    /**
     * Converts from URL to File
     * @return bool
     * @throws \MsWordToImageConvert\Exception
     */
    private function convertFromURLToFile()
    {
        $out = $this->tryOpenOutputFile();

        return $this->executeCurlPost([
            'url' => urlencode($this->input->getValue())
        ], [
            CURLOPT_FILE => $out
        ]);
    }

    /**
     * Executs a CURL post request
     * @param array $fields
     * @param array $curlOptions
     * @return mixed
     * @throws \MsWordToImageConvert\Exception
     */
    private function executeCurlPost(array $fields, $curlOptions = array())
    {
        $fieldsString = "";
        foreach ($fields as $key => $value) {
            $fieldsString .= $key . '=' . $value . '&';
        }
        rtrim($fieldsString, '&');

        $curlOptionsReal = [
            CURLOPT_URL => "http://msword2image.com/convert",
            CURLOPT_HEADER => 0,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $fieldsString
        ];

        foreach ($curlOptions as $key => $value) {
            $curlOptionsReal[$key] = $value;
        }

        $ch = curl_init();
        foreach ($curlOptionsReal as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        $result = curl_exec($ch);
        $error = curl_error($ch);

        if ($error !== "") {
            throw new \MsWordToImageConvert\Exception("cURL error: " . $error);
        }

        curl_close($ch);
        return $result;
    }
}
