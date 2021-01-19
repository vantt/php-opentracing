<?php

namespace Tracing\Enum;

class LogNameEnum {

  const EVENT = 'event'; // A stable identifier for some notable moment in the lifetime of a Span. For instance, a mutex lock acquisition or release or the sorts of lifetime events in a browser page load described in the Performance.timing specification. E.g., from Zipkin, "cs", "sr", "ss", or "cr". Or, more generally, "initialized" or "timed out". For errors, "error"

  const ERROR_KIND = 'error.kind'; // The type or “kind” of an error (only for event="error" logs). E.g., "Exception", "OSError"

  const ERROR_MESSAGE = 'error.message'; // A concise, human-readable, one-line message explaining the event. E.g., "Could not connect to backend", "Cache invalidation succeeded"

  const ERROR_STACK = 'error.stack'; // A stack trace in platform-conventional format; may or may not pertain to an error. E.g., "File \"example.py\", line 7, in \<module\>\ncaller()\nFile \"example.py\", line 5, in caller\ncallee()\nFile \"example.py\", line 2, in callee\nraise Exception(\"Yikes\")\n"

  const HTTP_REQUEST_HEADER = 'http.req.header';

  const HTTP_REQUEST_PARAM = 'http.req.param';

  const HTTP_RESPONSE_HEADER = 'http.resp.header';

  const HTTP_RESPONSE_DATA = 'http.resp.data';

}
