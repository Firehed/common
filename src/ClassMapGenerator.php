<?php

namespace Firehed\Common;

use Exception;
use BadMethodCallException;
use DomainException;
use FileFinder;

class ClassMapGenerator {

    // If other format constants are added, be sure to update the setFormat()
    // and generate() methods
    const FORMAT_AUTO = 'auto';
    const FORMAT_JSON = 'json';
    const FORMAT_PHP = 'php';

    private $format = self::FORMAT_AUTO;
    private $interface;
    private $method;
    private $namespace;
    private $output_file;
    private $path;
    private $writer;

    /**
     * Configure output format
     *
     * @param const __CLASS__::FORMAT_*
     * @return this
     */
    public function setFormat($format) {
        if ($this->output_file) {
            throw new BadMethodCallException(sprintf(
                "Call %s before %s",
                __METHOD__,
                __CLASS__.'::setOutputFile'));
        }
        switch ($format) {
        case self::FORMAT_JSON:
        case self::FORMAT_PHP:
            break;
        default:
            throw new DomainException("Invalid format");
        }
        $this->format = $format;
        return $this;
    } // setFormat

    /**
     * Path to recursively search from
     *
     * @param string Absolute path
     * @return this
     */
    public function setPath($path) {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        return $this;
    } // setPath

    /**
     * Assume this namespace prefix based on the file path
     *
     * @param string Namespace prefix
     * @return $this
     */
    public function setNamespace($namespace) {
        $this->namespace = trim($namespace, '\\');
        return $this;
    } // setNamespace

    /**
     * If set, found classes that do not implement this interface will be
     * skipped
     *
     * @param string Interface
     * @return this
     */
    public function setInterface($interface) {
        $this->interface = trim($interface, '\\');
        return $this;
    } // setInterface

    /**
     * Method to call on found classes that returns the mapping key
     *
     * @param string method name
     * @return this
     */
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    } // setMethod

    /**
     * Write the generated file to this path
     *
     * @param string Path for file output
     * @return this
     */
    public function setOutputFile($output_file, callable $writer = null) {
        if (!$writer) {
            $writer = 'file_put_contents';
        }
        $info = pathinfo($output_file);
        $ext = $info['extension'];
        // Automatically set output format from filename
        if (self::FORMAT_AUTO === $this->format) {
            switch ($ext) {
            case 'php':
                $this->format = self::FORMAT_PHP;
                break;
            case 'json':
                $this->format = self::FORMAT_JSON;
                break;
            default:
                throw new DomainException(
                    "Can not determine the format based on the filename");
            }
        }
        // Verify format/extension match
        elseif ($ext !== $this->format) {
            throw new DomainException(
                "File extension does not match output format");
        }

        $this->output_file = $output_file;
        $this->writer = $writer;
        return $this;
    } // setOutputFile

    /**
     * Execute code map generation
     *
     * @throws LogicException if misconfigured
     * @throws RuntimeException if files are bad
     * @return array The generated array
     */
    public function generate() {
        if (!$this->path) {
            throw new \BadMethodCallException(
                "Call setPath() before generate()");
        }
        if (!$this->method) {
            throw new \BadMethodCallException(
                "Call setMethod() before generate()");
        }

        $files = (new FileFinder($this->path))
            ->withType('f')
            ->withSuffix('php')
            ->find();

        $classes = [];
        foreach ($files as $file) {
            $class = $this->namespace.'\\'.
                str_replace('/', '\\', substr($file, 0, -4));

            $file_path = $this->path.DIRECTORY_SEPARATOR.$file;
            require_once $file_path;

            if (!class_exists($class, false)) {
                continue;
            }

            // See if the class was defined from within the directory we are
            // searching. If not, skip over it. This primarily for unit tests
            // based on the way one would *tend* to use this class, but ensures
            // that odd namespace/path combinations don't cause weird output
            $rc = new \ReflectionClass($class);
            if ($file_path !== $rc->getFileName()) {
                continue;
            }

            $obj = new $class;
            if ($this->interface && !$obj instanceof $this->interface) {
                continue;
            }

            $routes = (array)call_user_func([$obj, $this->method]);
            foreach ($routes as $route) {
                if (isset($classes[$route])) {
                    throw new Exception(sprintf(
                        "The class '%s' is already handling '%s'",
                        $classes[$route],
                        $route));
                }
                $classes[$route] = get_class($obj);
            }
        }

        $classes['@gener'.'ated'] = gmdate('c');
        switch ($this->format) {
        case self::FORMAT_JSON:
            $output = $this->buildJsonOutput($classes);
            break;
        case self::FORMAT_PHP:
            $output = $this->buildPhpOutput($classes);
            break;
        case self::FORMAT_AUTO:
            break;
        }

        if ($this->output_file) {
            call_user_func($this->writer, $this->output_file, $output);
        }
        return $classes;
    } // generate

    private function buildPhpOutput(array $classes) {
        $output = "<?php\n".
            "return ".var_export($classes, true).";\n";
        return $output;
    } // buildPhpOutput

    private function buildJsonOutput(array $classes) {
        $pretty = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
        return json_encode($classes, $pretty);
    } // buildJsonOutput

}
