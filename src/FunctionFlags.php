<?php

    namespace FunctionFlags;
    
    class FunctionFlags {
        // Limit debug_backtrace() to this amout of stack entries when trying to locate the
        // first occurance of this class. Set to 0 for unlimited.
        public static $BACKTRACE_LIMIT = 5;

        // Methods by name that can be called statically
        private static $static_public_whitelist = [
            "static_define",
            "static_isset"
        ];

        // Methods by name that can be called when instanced
        private static $inst_public_whitelist = [
            "inst_define",
            "inst_isset"
        ];

        public function __construct(string|array $flags = null) {
            // This array will contain instance defined flags
            $this->flags = [];

            // Define flags that were initialized with this class
            if (!empty($flags)) {
                $this->inst_define($flags);
            }
        }

        // We're using the PHP __call() and __callStatic() magic functions here in order to allow
        // methods to be called with the same name downstream. inst_define() and static_define() can
        // be called using FunctionFlags::define() and FunctionFlags->define().

        // Call static method on this class
        public static function __callStatic(string $method, array $args): mixed {
            // Static methods use this method prefix
            $method_prefixed = "static_" . $method;

            // Check that method exists on this class and is in whitelist of public static methods
            if (!in_array($method_prefixed, get_class_methods(__CLASS__)) && !in_array($method_prefixed, (__CLASS__)::$static_public_whitelist)) {
                throw new \BadMethodCallException("Method '${method}' does not exist");
            }

            return (__CLASS__)::{$method_prefixed}(...$args);
        }

        // Call instanced method on this class
        public function __call(string $method, array $args): mixed {
            // Instanced methods use this method prefix
            $method_prefixed = "inst_" . $method;

            // Check that method exists on this class and is in whitelist of public instanced methods
            if (!in_array($method_prefixed, get_class_methods(__CLASS__)) && !in_array($method_prefixed, (__CLASS__)::$inst_public_whitelist)) {
                throw new \BadMethodCallException("Method '${method}' does not exist");
            }

            return $this->{$method_prefixed}(...$args);
        }

        /* ---- */

        // Reserve a constant address space
        private static function addr_reserve(): int {
            // Set initial value when first flag is defined
            if (empty($_ENV[__CLASS__])) {
                $_ENV[__CLASS__] = 1;
            }

            // Increment counter
            $_ENV[__CLASS__]++;
            // Return counter as power of 2
            return $_ENV[__CLASS__] ** 2;
        }

        // Get flags from caller closest to this method on the call stack
        private static function get_flags_from_caller(): int|null {
            // Get call stack in reverse order
            $stack = array_reverse(debug_backtrace(0, (__CLASS__)::$BACKTRACE_LIMIT));

            // Find first occurance of this class name in call stack
            $idx = array_search(__CLASS__, array_column($stack, "class"));
            // Failed to locate this class
            if ($idx === false) {
                throw new Exception("Failed to retrieve flags from initator callable; Perhaps increase FunctionFlags::\$BACKTRACE_LIMIT");
            } elseif ($idx === 0) {
                // No parent callable. Method was probably called on its own
                return null;
            }

            // Get args array from initial caller by simply stepping back one entry in the reverse array
            $args = $stack[$idx - 1]["args"];

            // Return null if no arguments provided or not a valid int flag
            return !empty($args) && is_int(end($args)) ? end($args) : null;
        }

        /* ---- */

        // Define new constants
        private static function static_define(string|array $flags): array {
            // Convert to array
            $flags = is_array($flags) ? $flags : [$flags];

            $reserved = [];

            // Define constant for each flag with unique address
            foreach ($flags as $flag) {
                // Constant already defined with that name
                if (defined($flag)) {
                    // Pass existing address
                    $reserved[] = constant($flag);
                    continue;
                }

                // Reserve new address
                $addr = (__CLASS__)::addr_reserve();
                $reserved[] = $addr;

                define($flag, $addr);
            }

            // Return reserved addresses
            return $reserved;
        }

        // Check if a flag is set with bitwise AND of all flags
        private static function static_isset(int $flag): bool {
            $flags = (__CLASS__)::get_flags_from_caller();
            return $flags ? $flags & $flag : false;
        }

        /* ---- */

        // Define flag(s) for $this instance
        private function inst_define(string|array $flags) {
            // Convert to array
            $flags = is_array($flags) ? $flags : [$flags];

            // Append flag(s) to instance memory
            $this->flags = array_merge($this->flags, $this::static_define($flags));
        }

        // Check if flag is set and within $this scope
        private function inst_isset(int $flag): bool {
            // Return false if the flag is not in scope of $this instance
            if (!in_array($flag, $this->flags)) {
                return false;
            }

            // Filter flags that belong to this scope
            return $this::static_isset($flag);
        }
    }
