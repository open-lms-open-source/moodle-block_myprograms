// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is part of the Moodle apps support for the myprograms block.
 * Defines the function to be used from the mobile programs view template.
 *
 * @copyright   2022 Open LMS
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const allprograms = this.CONTENT_OTHERDATA.programs;

this.textFilter = '';
this.optionFilter = '';

if (Array.isArray(this.CONTENT_OTHERDATA.data) && this.CONTENT_OTHERDATA.data.length == 0) {
    // When there are no responses we receive an empty array instead of an empty object. Fix it.
    this.CONTENT_OTHERDATA.data = {};
}

/**
 * Filters list of programs based on search text
 */
this.filterTextChanged = (target) => {
    this.textFilter = target.value || '';

    this.filterPrograms();
}

this.filterOptionsChanged = (target) => {
    if (target) {
        this.optionFilter = target.value || 'allactive';
    } else {
        this.optionFilter = 'allactive';
    }

    this.filterPrograms();
}

this.filterPrograms = () => {
    let filteredprograms = allprograms;
    // Text filter and status filter.
    const value = this.textFilter.trim().toLowerCase();
    const option = this.optionFilter.trim().toLowerCase();

    if (filteredprograms.length > 0) {
        this.CONTENT_OTHERDATA.filteredprograms = filteredprograms.filter((program) => {
            // Filter first to make sure the program matches the selected status.
            let hasStatus = false;
            if(option === 'all') {
                hasStatus = true;
            } else {
                if (program.status) {
                    if (program.status.type.toLowerCase() === option) {
                        hasStatus = true;
                    } else if (program.status.type.toLowerCase() === 'open' && option === 'allactive') {
                        hasStatus = true;
                    } else if (program.status.type.toLowerCase() === 'future' && (option === 'open' || option === 'allactive')) {
                        hasStatus = true;
                    }
                } else if (option === 'allactive') {
                    // If the the program doesn't have a status, we assume it should show in the all active filter.
                    hasStatus = true; 
                }
            }

            // Filter by program name and tags. Name is a partial text match. Tags is a full text match.
            if (hasStatus === true) {
                let nameHasFilterText = (program.fullname.toLowerCase().indexOf(value) > -1);
                let tagHasFilterText = program.tags.filter((tag) => tag.displayname.toLowerCase() == value).length > 0;
                return nameHasFilterText || tagHasFilterText;
            }
            return false;
        });
    } else {
        this.CONTENT_OTHERDATA.filteredprograms = filteredprograms;
    }
}

this.filterOptionsChanged();
