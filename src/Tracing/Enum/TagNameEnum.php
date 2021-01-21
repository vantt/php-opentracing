<?php

namespace Tracing\Enum;

class TagNameEnum {

  const ERROR = 'error'; // true if and only if the application considers the operation represented by the Span to have failed

  const SERVICE = 'service'; // Current service name

  const APP_ENV = 'app.env';

  const APP_VERSION = 'app.version';

  const COMPONENT = 'component'; // The software package, framework, library, or module that generated the associated Span

  const HTTP_METHOD = 'http.method'; // HTTP method of the request for the associated Span. E.g., "GET", "POST"

  const HTTP_STATUS_CODE = 'http.status_code'; // HTTP response status code for the associated Span. E.g., 200, 503, 404

  const HTTP_URL = 'http.url'; // URL of the request being handled in this segment of the trace, in standard URI format. E.g., "https://domain.net/path/to?resource=here"

  const HTTP_URL_HOSTNAME = 'http.url.hostname'; // HTTP URL hostname

  const HTTP_URL_PORT = 'http.url.port'; // HTTP URL port

  const HTTP_URL_PATH = 'http.url.path'; // HTTP URL path

  const HTTP_PORT = 'http.port';

  const HTTP_PROTOCOL_VERSION = 'http.protocol_version';

  const PEER_SERVICE = 'peer.service'; // Remote service name (for some unspecified definition of "service"). E.g., "elasticsearch", "a_custom_microservice", "memcache"

  const PEER_HOSTNAME = 'peer.hostname'; // Remote hostname. E.g., "opentracing.io", "internal.dns.name"

  const PEER_PORT = 'peer.port';

  const PEER_INDEX = 'peer.index'; // E.g., Database type is "redis" using index 9

  const DB_TYPE = 'db.type'; // Database type. For any SQL database, "sql". For others, the lower-case database category, e.g. "cassandra", "hbase", or "redis"

  const DB_INSTANCE = 'db.instance'; // Database instance name. E.g., In java, if the jdbc.url="jdbc:mysql://127.0.0.1:3306/customers", the instance name is "customers"

  const DB_STATEMENT = 'db.statement'; // A database statement for the given database type. E.g., for db.type="sql", "SELECT * FROM wuser_table"; for db.type="redis", "SET mykey 'WuValue'"

  const DB_USER = 'db.user'; // Username for accessing database. E.g., "readonly_user" or "reporting_user"

}
