<?php

/**
 * Filter
 *
 * Global filters handler.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Filter {
    use Module,
        Filters {
          filter       as add;
          filterSingle as single;
          filterRemove as remove;
          filterWith   as with;
    }
}
