# Smoke

Smoke scope:

- Composer metadata validates.
- PSR-4 autoload is regenerated.
- Notification contract classes autoload.
- Unit contract files execute through package-local test command.
- Scope checker confirms changes stay inside launch record allowed files and
  evidence path.

No delivery, provider, template renderer, queue, admin or HTTP smoke exists in
this batch because no runtime notification surface is implemented.

