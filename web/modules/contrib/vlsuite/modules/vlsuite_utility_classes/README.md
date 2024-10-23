# VLSuite Utility Classes

A core feature of VLSuite. Within this module you will be able to create new utility classes and enable them to be used on any entity.

By default this module will work out of the box if you are using a Bootstrap 5 based theme. However, if your project is not using Bootstrap 5, you can define your own utility classes.


## Setting up new utility classes

You can define new utility classes on your theme, such as

```
bg-blue {
background-color: $blue;
}

bg-red {
background-color: $red;
}
```
After that, you may configure this utility class to be used for content editors on /admin/config/vlsuite/utility-classes. This form will ask for the utility class nomenclature and the elements where it will be available.

Example configuration a background-color utility class.

```
background-color:
    apply_to:
      'vlsuite_layout:section': 'vlsuite_layout:section'
      'vlsuite_layout:main_regions': 'vlsuite_layout:main_regions'
    visual_name: 'Background color'
    class_prefix: bg-
    values:
      primary:
        visual_name: Primary
        class_suffix: primary
      secondary:
        visual_name: Secondary
        class_suffix: secondary
      success:
        visual_name: Success
        class_suffix: success
```
