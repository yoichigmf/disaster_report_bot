There are a bunch of reasons why this is convoluted, mostly in building the URL to make the request:

1. You have to rely on an AJAX request, this example uses jQuery
2. To make a GetFeatureInfo request, you must provide a BBOX for a image, and the pixel coordinates for the part of the image that you want info from. A couple of squirrely lines of Leaflet code can give you that.
3. Output formats. The `info_format` parameter in the request. We don't know *a priori* which will be supported by a WMS that we might make a request to. See [Geoserver's docs](http://docs.geoserver.org/stable/en/user/services/wms/reference.html#getfeatureinfo) for what formats are available from Geoserver. That won't be the same from WMS to WMS, however.
4. WMS services return XML docs when there's a mistake in the request or in processing. This sends an HTTP 200, which jQuery doesn't think is an error.