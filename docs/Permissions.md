# The Permissions System

## Internal Roles

These are the basic roles that can be assigned to MCA users. They are called `Internal` because they are
the building blocks used by the external role voters, and as such should only be used by the
`PermissionResolver`, `External\Voters`, or `Tests`

## Roles (External)

These are the roles that define and determine fine-grained access control. Code in things like `Controllers`,
`templates`, etc. should be checking permissions using these roles.