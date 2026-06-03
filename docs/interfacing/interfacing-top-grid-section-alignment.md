# Interfacing top grid section alignment

The top panel must follow the same section contract as the body shell grid:

- primary-left section
- secondary-left section
- main section
- optional right section

Older runtime HTML may still render `sr-*` classes while the canonical source has moved toward the `interface-*` namespace. The provider baseline CSS therefore contains a temporary runtime-compatibility section that maps the old direct children of `sr-shell-top__inner` into the same grid sections used by the body.

This is a compatibility bridge for rendered markup only. It does not re-canonize `sr-*` as a source namespace.
