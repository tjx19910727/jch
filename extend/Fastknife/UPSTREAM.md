# fastknife/ajcaptcha

- Upstream: https://github.com/fastknifes/ajcaptcha
- Version: v2.1.0
- Commit: 7342c91606f90133d835c205e94e5c1b35360db2
- License: GPL-3.0-only
- Integration: vendored manually; the root Composer dependency list is unchanged.

Local path adaptation:

- The upstream `src` contents live directly under `extend/Fastknife` so the
  project's existing `extend/` autoloader resolves the `Fastknife\` namespace.
- Default resource lookups were adjusted to read from
  `extend/Fastknife/resources`.
