<?php

require_once('../../../config.php');
require_once('../lib.php');
require_once('ildmeta_delete_sponsor_form.php');
defined('MOODLE_INTERNAL') || die();

$courseid = optional_param('courseid', array(), PARAM_INT);
$sponsor_id = optional_param('id', array(), PARAM_INT);

$context = context_system::instance();

$url = new moodle_url('/local/ildmeta/pages/ildmeta_delete_sponsor.php', array('courseid' => $courseid));
// Prevent access for students/guests
if (!has_capability('local/ildmeta:delete_sponsor', context_system::instance())) redirect(new moodle_url('/'));

require_login();


$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('title', 'local_ildmeta'));
$PAGE->set_heading(get_string('heading', 'local_ildmeta'));


$tbl_meta = 'ildmeta';
$tbl_sponsor = 'ildmeta_sponsors';

$url = new moodle_url('/local/ildmeta/pages/ildmeta_delete_sponsor.php', array('courseid' => $courseid, 'id' => $sponsor_id));
$mform = new ildmeta_delete_sponsor_form($url);




if ($mform->is_cancelled()) {

    $url = new moodle_url('/local/ildmeta/pages/ildmeta.php', array('courseid' => $courseid));
    redirect($url);

} else if ($fromform = $mform->get_data()) {

    if (!$fromform->submitbutton) {
        $url = new moodle_url('/local/ildmeta/pages/ildmeta.php', array('courseid' => $courseid));
        redirect($url);
    } else {

        // first delete from ildmeta_sponsors
        $fields = $DB->get_records_sql('SELECT name FROM {ildmeta_sponsors} WHERE courseid = ? AND name LIKE ?', array('courseid' => $courseid, 'name' => '%' . $sponsor_id . ''));

        $error = false;

        foreach ($fields as $f) {
            $params = array('courseid' => $courseid, 'name' => $f->name);

            if (!$DB->delete_records($tbl_sponsor, $params)) {
                $error = true;
            }
        }

        // then adjust the counter in ildmeta
        $sql = "UPDATE {ildmeta} SET detailssponsor=detailssponsor-1 WHERE courseid = ?";
        $params = array('courseid' => $courseid);

        if (!$DB->execute($sql, $params)) {
            $error = true;
        }

        $url = new moodle_url('/local/ildmeta/pages/ildmeta.php', array('courseid' => $courseid));
        if ($error) {
            redirect($url, 'Fehler beim Löschen der Datensätze!', null, \core\output\notification::NOTIFY_ERROR);
        } else {
            redirect($url, 'Datensätze erfolgreich gelöscht!', null, \core\output\notification::NOTIFY_SUCCESS);
        }

    }

} else {

    echo $OUTPUT->header();

    $mform->display();

    echo $OUTPUT->footer();

}
