# VLSuite Layout

Registers a set of highly configurable layouts:

For now, the layouts go from 1-4 columns (more to come!), each one having two optionals regions:

- **Top:** Optional region often used to provide a section title. Does not create any space if left empty.
- **Bottom:** Optional region often used to provide a section footer. Does not create any space if left empty.
- **Main**: Depending on the selected layout the main region can be split up into X number of regions. Having selected a three-column layout, the result would look like this

Some of the layout configuration includes:
  - Section background-color/image/video.
  - Section width: Edge-to-edge or page max width.
  - Adjust columns width for each layout: For a 3 column, editors can specify a set of column widths like 25%/50%/25%, 33%/34%/33%, 25%/25%/50%...
  - Vertical/horizontal spacing.

