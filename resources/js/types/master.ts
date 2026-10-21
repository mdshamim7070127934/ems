export type ModuleStatus = 'active' | 'inactive';

export type ModuleSortColumn =
    | 'module_name'
    | 'status'
    | 'created_on'
    | 'updated_on';

export type MasterModule = {
    module_id: number;
    module_name: string;
    status: ModuleStatus;
    status_label: string;
    created_on: string;
    updated_on: string;
};

export type ModuleFilters = {
    search: string;
    sortBy: ModuleSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedModules = {
    data: MasterModule[];
    meta: PaginationMeta;
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type DropdownSortColumn =
    | 'name'
    | 'module_name'
    | 'status'
    | 'created_on'
    | 'updated_on';

export type MasterDropdown = {
    option_type_id: number;
    module_id: number;
    module_name: string;
    name: string;
    status: ModuleStatus;
    status_label: string;
    created_on: string;
    updated_on: string;
};

export type DropdownFilters = {
    search: string;
    sortBy: DropdownSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedDropdowns = {
    data: MasterDropdown[];
    meta: PaginationMeta;
};

export type DropdownModuleOption = {
    module_id: number;
    module_name: string;
};

export type DropdownTypeOption = {
    option_type_id: number;
    module_id: number;
    name: string;
};

export type DropdownParentOption = {
    id: number;
    option_type_id: number;
    option_value: string;
};

export type OptionValueSortColumn =
    | 'option_value'
    | 'dropdown_name'
    | 'module_name'
    | 'position'
    | 'status'
    | 'created_on'
    | 'updated_on';

export type MasterDropdownOptionValue = {
    id: number;
    option_type_id: number;
    dropdown_name: string;
    module_id: number;
    module_name: string;
    parent_id: number;
    parent_value: string | null;
    option_value: string;
    position: number | null;
    user_access_id: string | null;
    master_type: string | null;
    status: ModuleStatus;
    status_label: string;
    created_on: string;
    updated_on: string;
};

export type OptionValueFilters = {
    search: string;
    sortBy: OptionValueSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedOptionValues = {
    data: MasterDropdownOptionValue[];
    meta: PaginationMeta;
};

export type MenuSortColumn =
    | 'menu_name'
    | 'parent_name'
    | 'menu_route'
    | 'menu_order'
    | 'status'
    | 'created_at'
    | 'updated_at';

export type MasterMenu = {
    id: number;
    menu_parent_id: number;
    parent_name: string | null;
    menu_name: string;
    column_description: string | null;
    menu_route: string;
    menu_order: number;
    screen_name: string | null;
    web_icon: string | null;
    mobile_icon: string | null;
    status: ModuleStatus;
    status_label: string;
    created_at: string;
    updated_at: string;
};

export type MenuFilters = {
    search: string;
    sortBy: MenuSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedMenus = {
    data: MasterMenu[];
    meta: PaginationMeta;
};

export type MenuParentOption = {
    id: number;
    menu_name: string;
};

export type CountryStatus = 'active' | 'inactive' | 'delete';

export type CountrySortColumn =
    | 'name'
    | 'sortname'
    | 'currency'
    | 'phonecode'
    | 'timezone_name'
    | 'status';

export type MasterCountry = {
    id: number;
    sortname: string;
    name: string;
    currency: string;
    phonecode: number;
    timezone_id: number | null;
    timezone_name: string | null;
    timezone_display_name: string | null;
    status: CountryStatus;
    status_label: string;
};

export type TimezoneOption = {
    id: number;
    name: string;
    display_name: string;
    utc_offset: string;
};

export type CountryFilters = {
    search: string;
    sortBy: CountrySortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedCountries = {
    data: MasterCountry[];
    meta: PaginationMeta;
};

export type CountryOption = {
    id: number;
    name: string;
};

export type StateSortColumn =
    | 'name'
    | 'country_name'
    | 'status'
    | 'created_on'
    | 'updated_on';

export type MasterState = {
    id: number;
    country_id: number;
    country_name: string;
    name: string;
    status: ModuleStatus;
    status_label: string;
    created_on: string;
    updated_on: string;
};

export type StateFilters = {
    search: string;
    sortBy: StateSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedStates = {
    data: MasterState[];
    meta: PaginationMeta;
};

export type StateOption = {
    id: number;
    country_id: number;
    name: string;
};

export type CitySortColumn =
    | 'name'
    | 'country_name'
    | 'state_name'
    | 'status'
    | 'created_on'
    | 'updated_on';

export type MasterCity = {
    id: number;
    country_id: number;
    country_name: string;
    state_id: number;
    state_name: string;
    name: string;
    status: ModuleStatus;
    status_label: string;
    created_on: string;
    updated_on: string;
};

export type CityFilters = {
    search: string;
    sortBy: CitySortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedCities = {
    data: MasterCity[];
    meta: PaginationMeta;
};

export type TimezoneSortColumn =
    | 'name'
    | 'display_name'
    | 'utc_offset'
    | 'status'
    | 'created_on';

export type MasterTimezone = {
    id: number;
    name: string;
    code: string;
    utc_offset: string;
    display_name: string;
    status: ModuleStatus;
    status_label: string;
    created_on: string;
    updated_on: string;
};

export type TimezoneFilters = {
    search: string;
    sortBy: TimezoneSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedTimezones = {
    data: MasterTimezone[];
    meta: PaginationMeta;
};

export type TimezoneIdentifierOption = {
    name: string;
    code: string;
    utc_offset: string;
    display_name: string;
};

export type UserOption = {
    id: number;
    name: string;
};

export type GroupSortColumn =
    | 'group_name'
    | 'owner_name'
    | 'status'
    | 'created_at'
    | 'updated_at';

export type MasterGroup = {
    id: number;
    group_name: string;
    description: string;
    owner_id: number;
    owner_name: string;
    status: ModuleStatus;
    status_label: string;
    created_at: string;
    updated_at: string;
};

export type GroupFilters = {
    search: string;
    sortBy: GroupSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedGroups = {
    data: MasterGroup[];
    meta: PaginationMeta;
};

export type GroupOption = {
    id: number;
    name: string;
};

export type RegionSortColumn =
    | 'region_name'
    | 'country_name'
    | 'owner_name'
    | 'status'
    | 'created_at'
    | 'updated_at';

export type MasterRegion = {
    id: number;
    region_name: string;
    country_id: number;
    country_name: string;
    owner_id: number;
    owner_name: string;
    status: ModuleStatus;
    status_label: string;
    created_at: string;
    updated_at: string;
};

export type RegionFilters = {
    search: string;
    sortBy: RegionSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedRegions = {
    data: MasterRegion[];
    meta: PaginationMeta;
};

export type RegionOption = {
    id: number;
    country_id: number;
    name: string;
};

export type BranchSortColumn =
    | 'branch_name'
    | 'branch_code'
    | 'country_name'
    | 'region_name'
    | 'group_name'
    | 'manager_name'
    | 'status'
    | 'created_on'
    | 'updated_on';

export type MasterBranch = {
    id: number;
    branch_name: string;
    geofencing: boolean;
    geofencing_range: string | null;
    branch_country_code: string | null;
    branch_fbcountry_code: string;
    branch_manager: number;
    manager_name: string;
    country_id: number;
    country_name: string;
    region_id: number;
    region_name: string;
    group_id: number;
    group_name: string;
    branch_code: string;
    owner_id: number | null;
    owner_name: string | null;
    man_power: number | null;
    retail_man_power: number | null;
    created_by: number;
    created_by_name: string;
    last_modified_by: number;
    last_modified_by_name: string;
    status: ModuleStatus;
    status_label: string;
    created_on: string;
    updated_on: string;
};

export type BranchFilters = {
    search: string;
    sortBy: BranchSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedBranches = {
    data: MasterBranch[];
    meta: PaginationMeta;
};

export type RoleSortColumn =
    | 'role_name'
    | 'display_name'
    | 'is_active'
    | 'created_at'
    | 'updated_at';

export type MasterRole = {
    id: number;
    role_name: string;
    display_name: string;
    is_active: boolean;
    is_super_admin: boolean;
    is_protected: boolean;
    status: ModuleStatus;
    status_label: string;
    menu_ids: number[];
    created_at: string;
    updated_at: string;
};

export type RoleMenuOption = {
    id: number;
    menu_name: string;
    menu_route: string;
};

export type RoleMenuGroup = {
    id: number;
    label: string;
    menus: RoleMenuOption[];
};

export type RoleFilters = {
    search: string;
    sortBy: RoleSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedRoles = {
    data: MasterRole[];
    meta: PaginationMeta;
};

export type UserSortColumn =
    | 'first_name'
    | 'last_name'
    | 'email'
    | 'role_name'
    | 'branch_name'
    | 'reporting_person_name'
    | 'status'
    | 'created_at';

export type MasterUser = {
    id: number;
    first_name: string | null;
    last_name: string | null;
    name: string;
    email: string;
    username: string | null;
    role_id: number | null;
    role_name: string | null;
    branch_id: number | null;
    branch_name: string | null;
    reporting_person_id: number | null;
    reporting_person_name: string | null;
    extra_branch_ids: number[];
    extra_branch_names: string;
    exclude_branch_ids: number[];
    exclude_branch_names: string;
    status: ModuleStatus;
    status_label: string;
    is_available: boolean;
    last_login: string | null;
    created_at: string;
    updated_at: string;
};

export type RoleOption = {
    id: number;
    name: string;
};

export type BranchOption = {
    id: number;
    name: string;
};

export type UserFilters = {
    search: string;
    sortBy: UserSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedUsers = {
    data: MasterUser[];
    meta: PaginationMeta;
};

export type EmailTypeSortColumn =
    | 'code'
    | 'name'
    | 'sort_order'
    | 'status'
    | 'created_at';

export type MasterEmailType = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    sort_order: number;
    status: ModuleStatus;
    status_label: string;
    created_at: string;
    updated_at: string;
};

export type EmailTypeFilters = {
    search: string;
    sortBy: EmailTypeSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedEmailTypes = {
    data: MasterEmailType[];
    meta: PaginationMeta;
};

export type EmailTypeOption = {
    id: number;
    code: string;
    name: string;
    status: ModuleStatus;
    disabled: boolean;
};

export type EditorSettingSortColumn =
    | 'code'
    | 'name'
    | 'sort_order'
    | 'status'
    | 'is_default'
    | 'created_at';

export type MasterEditorSetting = {
    id: number;
    code: string;
    code_label: string;
    name: string;
    description: string | null;
    is_default: boolean;
    sort_order: number;
    status: ModuleStatus;
    status_label: string;
    created_at: string;
    updated_at: string;
};

export type EditorSettingFilters = {
    search: string;
    sortBy: EditorSettingSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedEditorSettings = {
    data: MasterEditorSetting[];
    meta: PaginationMeta;
};

export type EmailEnumOption = {
    value: string;
    label: string;
};

export type EmailAccountSortColumn =
    | 'name'
    | 'provider'
    | 'from_email'
    | 'is_default'
    | 'status'
    | 'created_at';

export type MasterEmailAccount = {
    id: number;
    name: string;
    provider: string;
    provider_label: string;
    mailer: string;
    mailer_label: string;
    from_name: string;
    from_email: string;
    username: string | null;
    smtp_host: string | null;
    smtp_port: number | null;
    smtp_encryption: string | null;
    smtp_encryption_label: string | null;
    uses_oauth: boolean;
    connection_status: 'connected' | 'reconnect_required' | 'disconnected' | 'smtp';
    connection_status_label: string;
    reauthorization_required: boolean;
    oauth_error: string | null;
    oauth_token_expires_at: string | null;
    last_token_refresh_at: string | null;
    has_password: boolean;
    has_oauth_access_token: boolean;
    has_oauth_refresh_token: boolean;
    is_default: boolean;
    status: ModuleStatus;
    status_label: string;
    email_type_ids: number[];
    email_type_names: string;
    created_at: string;
    updated_at: string;
};

export type EmailAccountFilters = {
    search: string;
    sortBy: EmailAccountSortColumn;
    sortOrder: 'asc' | 'desc';
    page: number;
    pageSize: number;
};

export type PaginatedEmailAccounts = {
    data: MasterEmailAccount[];
    meta: PaginationMeta;
};
