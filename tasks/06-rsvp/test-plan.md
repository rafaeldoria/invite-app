# RSVP Test Plan

## Domain and Backend Matrix

| Scenario | Expected |
| --- | --- |
| General confirm | One guest created, confirmed, capability issued, correct counts. |
| General decline | One declined guest, both counts zero. |
| Individual response | Existing pending guest updated; no new guest. |
| Safe update | Correct capability updates the same guest and timestamp. |
| Replay/double click | Same result, no duplicate for the same capability/invitation. |
| Name collision | Two general guests may share a name; neither can edit by name. |
| Tamper/mismatch | Non-disclosing 404/invalid response, no mutation. |
| Limits | Counts 0 and 20 accepted; negative, decimal, 21, string rejected. |
| Status switch | Decline clears prior counts; later confirmation accepts new counts. |

## Security and Privacy

- Raw response token is stored only client-side/in URL as designed; database stores a hash when applicable.
- Tokens are absent from logs, exception context, public metadata, generic public props, and share messages.
- Rate limits do not lock out all guests behind one NAT after normal use.
- Personalized RSVP responses have no shared public-cache behavior.
- A guest capability cannot access another event or guest.

## End-to-End UX

- Open public link, confirm with companions, see receipt, update to decline.
- Open individual invitation, confirm without retyping identity, update counts.
- Open invalid link and recover without information leakage.
- Complete on 320px viewport, keyboard-only, both locales/themes.
- Validate party-size wording for adults/children and singular/plural cases.

## Exit Criteria

- Domain invariants and concurrency/replay tests pass on PostgreSQL.
- Both public entry modes pass end to end.
- No token or guest-list leakage is found.
- Dashboard/guest status integration reflects changes on next visit.

