# What This Is

This is a lightweight service bus so you can do your transforms
without it costing too much.

Each input request that isn't handled locally (including trivial
404s) is passed to another service with either input transformed
or output transformed or both or neither.

Input requests are stored while they're being worked on; likewise
output requests and input responses. Request/response bodies are
only stored to the extent that it is cheap to do so - thus known-large
message bodies are not stored, and streaming message bodies may be
stored if they're known to be short when the stream completes.
Authentication data is not retained. Stored message bodies are
encrypted to more provably prevent their interception and have a
maximum lifetime of 24 hours to mitigate the chance of them being
extracted after the fact. Request/response data may be retained for
a short time after request processing to aid in debugging.

Overhead for this service should be only about 10ms when configured
correctly for cases with no meaningful transforms.

# Intended Use Cases

This is specifically intended to help with network API versioning, so you can
maintain compatibility for an old version without having to keep and maintain
two or more network services. If doing so you still need to have functionality
equivalent to the old version somewhere, and you should pay particular attention
to whether you're inflating an O(1) request into O(n) or worse.

As a secondary concern, this can also handle cases where you may intend some
level of request abstraction which would not be practical on the backend
services themselves, for example combining data from multiple services.