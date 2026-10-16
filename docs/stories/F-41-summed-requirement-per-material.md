---
id: F-41
title: The cost engine gives the total need of each material, summed over every line
type: tech
phase: 5
priority: Must
size: S
status: Planned
created: 2026-10-16
tags:
  - tech
  - phase-5
---

# F-41 · The cost engine gives the total need of each material, summed over every line

> [!abstract] Description
> The cost engine already adds up what one unit of a product needs of each material, waste included, when it works out "Can produce" and
> the bottleneck. It doesn't hand that total to the rest of the app. The material page ("What you can make with it") reads the figure
> from the material's single composition line instead, which is right only because a material can't appear twice in one composition.
> This item makes the cost engine return the total need of every material, so that the material page, and from S-53 the "Start cutting"
> window, use one figure that is correct by construction. It is Phase 5's tech allowance and comes before S-53, which multiplies that
> figure by the units cut.

> [!info] How it works today
> Denim Jacket needs 1.98 m of Denim 12oz Indigo per unit (1.8 m plus 10 % waste). The cost engine sums each material over all its lines
> to find "Can produce 112" and the bottleneck "Metal shank button 17mm", but it returns only the per-line figures. The material page of
> Denim 12oz Indigo shows "Needs per unit" 1.98 m and "This stock covers" 126 units by picking the one line where the denim appears. If a material
> ever sat on two lines, the page would show the first line's figure while "Can produce" used the sum.

## Acceptance criteria
- [ ] **AC-1 · One figure per material.** For Denim Jacket the cost engine returns, for each of its four materials, the need per unit
  waste included (Denim 12oz Indigo 1.98 m, Metal shank button 17mm 8 pc, Polyester thread 126 m, Woven brand label 1 pc), the units its
  stock covers (126, 112, 158, 400) and which one is the bottleneck (the button). The unit cost (TND 35.754), "Can produce 112" and the
  bottleneck don't change.
- [ ] **AC-2 · Same material on two lines.** In the engine's own tests, a composition with the button on two lines of 4 pc each returns
  one entry for the button, needing 8 pc per unit and covering 112 units, exactly as one line of 8 pc does.
- [ ] **AC-3 · The material page uses it.** Denim 12oz Indigo's "What you can make with it" still shows Denim Jacket with "Needs per
  unit" 1.98 m, "This stock covers" 126 units, "Can make now" 112 units and its next limit, now read from the engine's total for the material.
- [ ] **AC-4 · Exact for what comes next.** Next to the 4-decimal figure, the engine keeps the unrounded need per unit (e.g. 0.0001 m at
  0.01 % waste is 0.00010001 m), so that S-53 can multiply it by the units cut without losing precision.

---

## Technical work
> [!warning] Complex · CRITICAL · backend · 1 commit
> Tech-lead · 2026-10-16 · approved 2026-10-16
> Why complex: it changes what the costing domain returns (the core maths), and S-53's stock amounts are built on it.

### In short
The cost engine (`CostingCalculator`) already builds, in its first pass, the total need per material. This item turns that internal
figure into a public part of its result: one entry per material with the need per unit (rounded to 4 decimals for display, and exact for
further maths), the stock, the units that stock covers and whether it is the bottleneck. The material page's service then reads that
entry instead of looking for "the" line of the material. No screen, route or page data changes, and the existing golden figures stay
the same, which the existing tests prove.

### Steps

| Step       | What it does                                         | Proves                 | Lane     | Who     | Size       |
| ---------- | ---------------------------------------------------- | ---------------------- | -------- | ------- | ---------- |
| **F-41-a** | The engine returns the summed need of every material | AC-1, AC-2, AC-3, AC-4 | CRITICAL | Backend | ~120 lines |

The step keeps `composer test`, `npx vp test`, `npm run check`, `npm run types:check` and `npm run build` green. No database change, so no
parity run is needed for this step alone (the S-53 build runs it).

#### F-41-a · The engine returns the summed need of every material
**Commit:** `refactor(costing): expose the summed requirement per material (F-41)`

**Tests**
- Domain, golden (AC-1): the Denim Jacket's four requirements with exact strings (`"1.9800"`, `"8.0000"`, `"126.0000"`, `"1.0000"`),
  units possible 126 · 112 · 158 · 400, the button flagged as bottleneck, and the order of first appearance.
- Domain, two lines (AC-2): button 4 + 4 pc → one requirement of `"8.0000"`, 112 units; and two lines with different waste
  (1.0000 at 0 % + 1.0000 at 10 %) → `"2.1000"`.
- Domain, exact figure (AC-4): 0.0001 at 0.01 % waste → `perUnit` `"0.0001"`, `perUnitExact` `"0.00010001"`.
- Domain, empty composition: `requirements` is an empty list.
- Material page (AC-3): the existing `MaterialUsageService` and material-page tests stay green unchanged (the Denim and button golden
  rows); no new test there.

> [!example]- Files (5)
> - `app/Domain/Costing/MaterialRequirement.php` (new)
> - `app/Domain/Costing/{CostingCalculator,CostingResult}.php`
> - `app/Services/Costing/MaterialUsageService.php`
> - `tests/Unit/Domain/Costing/CostingCalculatorTest.php`

### Contract (frozen before dispatch)
No route, page data or `domain.ts` change. The PHP the S-53 build relies on:

```php
namespace App\Domain\Costing;

/** The total need of one material for one unit of product, all its BOM lines summed (costing skill: required(m)). */
final readonly class MaterialRequirement
{
    public function __construct(
        public int $materialId,
        public string $materialName,
        public string $unit,
        public string $perUnit,       // Σ effective_qty over the material's lines, scale 4, HalfUp (display)
        public string $perUnitExact,  // the same sum, unrounded (at most 8 decimals): what any multiplication starts from
        public string $stockOnHand,   // scale 4
        public int $unitsPossible,    // ⌊ stock / perUnitExact ⌋
        public bool $isBottleneck,
    ) {}
}

final readonly class CostingResult
{
    /** @param list<MaterialRequirement> $requirements one per material, in the order each first appears in the BOM; [] for an empty BOM */
    public function __construct(
        public CostBreakdown $cost,
        public ProductionCapacity $capacity,
        public array $requirements,
    ) {}
}
```

### Decisions
- **Both a rounded and an exact figure.** `perUnit` follows the output rule (scale 4, HalfUp) for screens; `perUnitExact` exists only so
  that a later multiplication (units cut, S-53) rounds once, at the end (ADR-0002 rule 4). The arch rule "the costing service does no
  arithmetic" stays: `MaterialUsageService` only reads `perUnit` and `unitsPossible`.
- **`LineCost::$unitsPossible` stays** (the product page's composition table shows it per line, with the material's total); nothing on
  screen changes.
- No ADR of its own: ADR-0014 records how S-53 uses it.

### Risks
- `CostingResult` gains a required constructor argument → the developer updates every place that builds one (grep `new CostingResult`,
  tests included); larastan catches a missed one.
- A hidden change to the golden figures → the existing golden tests for the product page, the material page and the dashboard run
  unchanged and must stay green.
