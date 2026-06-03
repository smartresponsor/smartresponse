# Viewing W17 — Adjudicating governance workbench boundary

This wave moves the Adjudicating governance review workbench away from direct
Twig rendering in the producer controller.

## Scope

- `Adjudicating/src/Controller/Web/ReviewWorkbenchController.php`
- `Interfacing/template/governance/*`

## Boundary rule

The controller still builds the governance workbench data, forms, filters,
capabilities, runtime context, and audit report. It no longer calls
`$this->render('review/workbench.html.twig', ...)` for the GET workbench page.
It now returns a neutral Viewing payload:

- `surface: governance`
- `operation: workbench`
- `component: Adjudicating`

`Viewing` owns final response rendering and resolves the passive Interfacing
noun-surface template `@Interfacing/governance/workbench.html.twig`.

POST actions, redirects, CSRF checks, form handling, and access-denial responses
remain unchanged.
