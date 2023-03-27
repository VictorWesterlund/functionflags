<?php

		namespace FunctionFlags;
    
    class FunctionFlags {
        // Limit value for debug_backtrace()
        private static $trace_limit = 5;

        // Reserve a constant address space
        private static function addr_reserve(): int {
            // Set initial value when first flag is defined
            if (empty($_ENV[__CLASS__])) {
                $_ENV[__CLASS__] = 0;
            }

            // Increment counter
            $_ENV[__CLASS__]++;
            // Return counter as power of 2
            return $_ENV[__CLASS__] ** 2;
        }

        // Get flags from caller closest to this method on the call stack
        private static function get_flags_from_caller(): int|null {
            // Get call stack
            $stack = debug_backtrace(0, (__CLASS__)::$trace_limit);

            // Extract class names from callstack in reverse order and find the first occurance of this class in the backtrace
            $idx = array_search(__CLASS__, array_reverse(array_column($stack, "class")));
            // Failed to locate this class in a full backtrace
            if ($idx === false) {
                throw new Exception("Failed to retrieve flags from initator callable");
            }

            // Get args array from stack entry by index
            $args = $stack[$idx]["args"];

            return !empty($args) ? end($args) : null;
        }

        /* ---- */

        // Define new constants
        public static function define(string|array $flags) {
            // Convert to array
            $flags = is_array($flags) ? $flags : [$flags];

            // Define constant for each flag with unique address
            foreach ($flags as $flag) {
                define($flag, (__CLASS__)::addr_reserve());
            }
        }

        // Check if a flag is set with bitwise AND of all flags
        public static function isset(int $flag): bool|null {
            $flags = FunctionFlags::get_flags_from_caller();
            return $flags ? $flags & $flag : null;
        }
    }
